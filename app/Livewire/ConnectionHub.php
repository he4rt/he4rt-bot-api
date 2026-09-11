<?php

declare(strict_types=1);

namespace App\Livewire;

use Filament\Notifications\Notification;
use He4rt\Identity\Auth\Actions\ConfirmOAuthMerge;
use He4rt\Identity\Auth\Actions\ResolvePendingOAuthMerge;
use He4rt\Identity\Auth\DTOs\PendingOAuthMergeDTO;
use He4rt\Identity\ExternalIdentity\Actions\ConnectApiKeyIdentity;
use He4rt\Identity\ExternalIdentity\Enums\CredentialsType;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Exceptions\InvalidApiKeyException;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ConnectionHub extends Component
{
    public string $panel = 'app';

    public bool $showMergeModal = false;

    #[Locked]
    public ?string $mergeTargetId = null;

    public bool $showApiKeyModal = false;

    public ?string $apiKeyProvider = null;

    #[Validate(rule: 'required|string|min:10')]
    public string $apiKey = '';

    public function mount(): void
    {
        $this->panel = filament()->getCurrentPanel()?->getId() ?? 'app';
        $this->checkPendingMerge();
    }

    public function render(): View
    {
        if ($this->panel === 'admin') {
            return view('livewire.connection-hub-admin', [
                'tenantProviders' => $this->getTenantProviders(),
                'supportedProviders' => $this->getOAuthProviders(),
                'panel' => $this->panel,
            ]);
        }

        return view('livewire.connection-hub', [
            'userProviders' => $this->getUserProviders(),
            'providerGroups' => IdentityProvider::supportedProvidersByCredentialsType(),
            'panel' => $this->panel,
            'mergeTarget' => $this->getMergeTarget(),
        ]);
    }

    public function connect(IdentityProvider $provider): void
    {
        $usesApiKey = $provider->getCredentialsType() === CredentialsType::ApiKey;

        if ($usesApiKey) {
            $this->apiKeyProvider = $provider->value;
            $this->apiKey = '';
            $this->resetValidation();
            $this->showApiKeyModal = true;

            return;
        }

        $this->redirect(route('oauth.redirect', [
            'panel' => $this->panel,
            'provider' => $provider->value,
        ]));
    }

    public function saveApiKey(ConnectApiKeyIdentity $action): void
    {
        $this->validate();

        if ($this->apiKeyProvider === null) {
            return;
        }

        $provider = IdentityProvider::from($this->apiKeyProvider);

        /** @var User $user */
        $user = auth()->user();

        try {
            $action->execute($user, $provider, $this->apiKey);
        } catch (InvalidApiKeyException) {
            $this->addError('apiKey', 'Chave inválida ou sem permissão. Gere uma nova em dev.to → Settings → Extensions → DEV Community API Keys.');

            return;
        }

        $this->closeApiKeyModal();

        Notification::make()
            ->title($provider->getLabel().' conectado com sucesso')
            ->success()
            ->send();
    }

    public function closeApiKeyModal(): void
    {
        $this->showApiKeyModal = false;
        $this->apiKeyProvider = null;
        $this->apiKey = '';
        $this->resetValidation();
    }

    public function confirmMerge(
        ConfirmOAuthMerge $action,
        ResolvePendingOAuthMerge $resolvePendingMerge,
    ): void {
        $pending = $resolvePendingMerge->execute(session()->get('oauth_merge_pending'));

        if (!$pending instanceof PendingOAuthMergeDTO) {
            $this->cancelMerge();

            return;
        }

        /** @var User $currentUser */
        $currentUser = auth()->user();

        $oldUser = $action->execute($currentUser, $pending);

        if (!$oldUser instanceof User) {
            $this->cancelMerge();

            return;
        }

        session()->forget('oauth_merge_pending');
        $this->showMergeModal = false;
        $this->mergeTargetId = null;

        Auth::login($oldUser);

        Notification::make()
            ->title('Contas unificadas com sucesso')
            ->success()
            ->send();

        $this->redirect(filament()->getCurrentPanel()->getUrl());
    }

    public function cancelMerge(): void
    {
        session()->forget('oauth_merge_pending');
        $this->showMergeModal = false;
        $this->mergeTargetId = null;
    }

    public function disconnect(IdentityProvider $provider): void
    {
        $identity = auth()->user()
            ->providers()
            ->where('provider', $provider->value)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            Notification::make()
                ->title('No active connection found for '.$provider->getLabel())
                ->warning()
                ->send();

            return;
        }

        $identity->update(['disconnected_at' => now()]);

        Notification::make()
            ->title($provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }

    public function disconnectById(string $identityId): void
    {
        $identity = ExternalIdentity::query()
            ->where('id', $identityId)
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->first();

        if (!$identity) {
            Notification::make()
                ->title('Connection not found')
                ->warning()
                ->send();

            return;
        }

        $identity->update(['disconnected_at' => now()]);

        Notification::make()
            ->title($identity->provider->getLabel().' disconnected successfully')
            ->success()
            ->send();
    }

    private function checkPendingMerge(): void
    {
        $pending = session()->get('oauth_merge_pending');

        if (!is_array($pending) || !is_string($pending['conflicting_user_id'] ?? null)) {
            return;
        }

        $this->mergeTargetId = $pending['conflicting_user_id'];
        $this->showMergeModal = true;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function getMergeTarget(): ?array
    {
        if ($this->mergeTargetId === null) {
            return null;
        }

        $user = User::query()->find($this->mergeTargetId);

        if (!$user instanceof User) {
            return null;
        }

        $messagesCount = ExternalIdentity::query()
            ->where('model_type', (new User)->getMorphClass())
            ->where('model_id', $user->id)
            ->withCount('messages')
            ->get()
            ->sum('messages_count');

        return [
            'username' => $user->username,
            'created_at' => $user->created_at?->format('d/m/Y'),
            'messages_count' => $messagesCount,
        ];
    }

    /**
     * Providers de tenant são sempre OAuth: uma chave de API é pessoal.
     *
     * @return array<int, IdentityProvider>
     */
    private function getOAuthProviders(): array
    {
        return IdentityProvider::supportedProvidersByCredentialsType()[CredentialsType::OAuth2->value] ?? [];
    }

    /** @return Collection<int, ExternalIdentity> */
    private function getUserProviders(): Collection
    {
        return auth()->user()->providers()->get();
    }

    /** @return Collection<int, ExternalIdentity> */
    private function getTenantProviders(): Collection
    {
        return ExternalIdentity::query()
            ->where('model_type', 'tenant')
            ->whereNotNull('connected_at')
            ->whereNull('disconnected_at')
            ->with('connectedByUser')
            ->get();
    }
}
