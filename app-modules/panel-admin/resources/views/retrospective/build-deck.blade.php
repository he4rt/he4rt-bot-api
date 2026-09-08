@php
    use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
    use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode;

    $selection = $this->selection();
    $mode = $selection->mode;
    $record = $this->getRetrospective();
    $status = $record->status;
@endphp

<x-filament-panels::page>
    {{-- O design system do deck, o mesmo do portal. Todo ele é escopado sob
         `.retro`, então entra no painel sem alcançar nada do Filament. --}}
    @vite(['app-modules/portal/resources/css/retrospective.css'])

    {{--
        Altura fixa: o builder é uma FERRAMENTA, não um documento. Rolar a página
        para achar a tira ou o inspector é o que o layout anterior cobrava; aqui as
        três áreas dividem uma viewport e cada uma rola por dentro se precisar.

        O poll do job de publicação mora aqui em cima porque vale para a tela toda.
    --}}
    <div
        style="--builder-max-width: 1600px"
        class="grid grid-cols-1 gap-3 px-1 xl:h-[calc(100vh-11rem)] xl:min-h-[34rem] xl:grid-cols-[minmax(0,1fr)_17rem] xl:grid-rows-[minmax(0,1fr)_auto] xl:gap-4 xl:ps-6"
        @if ($status === RetrospectiveStatus::Publishing) wire:poll.3s="refreshStatus" @endif
    >
        {{--
            Preview. Passa pelo MESMO ComposeDeck e pelas MESMAS partials da página
            publicada — a única garantia de que o preview não mente é ser
            literalmente a mesma coisa (ADR-0002).

            `min-h-0` nas duas pontas: sem ele um filho flex se recusa a encolher
            abaixo do conteúdo e o deck estoura a linha do grid em vez de caber nela.
        --}}
        <div class="flex min-h-0 min-w-0 flex-col gap-2 xl:col-start-1 xl:row-start-1">
            {{-- Barra fina no lugar do cabeçalho de Section: diz ONDE o operador
                 está (slide e posição no deck) e qual arquivo desenha isso. Um
                 título "Preview" sobre um deck em tela cheia não informava nada. --}}
            <div class="mx-auto flex w-full max-w-[var(--builder-max-width)] shrink-0 flex-wrap items-center gap-x-3 gap-y-1 text-sm">
                <span class="font-semibold text-gray-950 dark:text-white">{{ $this->selectionLabel() }}</span>

                <span class="font-mono text-xs text-gray-400 dark:text-gray-500">
                    {{ $this->previewIndex() + 1 }} / {{ $this->slideTotal() }}
                </span>

                @if ($record->needsRepublish())
                    <x-filament::badge color="warning" icon="heroicon-m-exclamation-triangle" size="xs">
                        Republique: exclusion mudou depois de publicar
                    </x-filament::badge>
                @endif

                {{--
                    O arquivo do que está selecionado, para copiar direto no editor.
                    Encurta o caminho entre ver algo torto no preview e abrir o blade
                    certo — a convenção kind -> partial mora no portal (SlideView).
                --}}
                @if ($path = $this->viewPath())
                        <div
                            x-data="{ copied: false }"
                            class="flex min-w-0 items-center gap-1.5 xl:ms-auto"
                        >
                            {{-- Renderizado no servidor, não via x-text: o caminho é a
                                 informação, e ela não pode depender do Alpine ter subido. --}}
                            <code
                                x-ref="path"
                                title="{{ $path }}"
                                class="min-w-0 truncate rounded-md bg-gray-50 px-2 py-1 font-mono text-xs text-gray-600 dark:bg-white/5 dark:text-gray-400"
                            >{{ $path }}</code>

                            <x-filament::icon-button
                                icon="heroicon-m-clipboard-document"
                                x-show="! copied"
                                size="sm"
                                color="gray"
                                label="Copiar caminho da view"
                                x-on:click="
                                    navigator.clipboard.writeText($refs.path.textContent.trim()).then(() => {
                                        copied = true
                                        setTimeout(() => (copied = false), 1500)
                                    })
                                "
                            />

                            <x-filament::icon-button
                                icon="heroicon-m-clipboard-document-check"
                                x-show="copied"
                                x-cloak
                                size="sm"
                                color="success"
                                label="Caminho copiado"
                            />
                        </div>
                @endif
            </div>

                {{--
                    O deck de verdade, no mesmo DOM do builder: mesmas partials e
                    mesmo ComposeDeck da página pública, sem iframe no meio. O host
                    (.retro-embed) vira o containing block que faz o deck — `fixed`
                    quando ELE é a página — caber nesta coluna.

                    Quem manda o preview pular para o slide selecionado é o servidor,
                    via $this->js() depois de cada seleção: o índice muda a cada
                    render, e um x-data só seria avaliado na primeira vez.
                --}}
                {{--
                    Island: selecionar na estrutura ou digitar no inspector re-renderiza
                    o resto da página, mas NÃO este fragmento — o morph reescreveria o
                    deck com HTML sem as classes que o Alpine aplicou (slide ativo
                    sumia) e cada clique pagaria uma recoleta das fontes. O deck só
                    re-renderiza quando algo foi salvo (renderIsland em refreshPreview).

                    O deck avisa quando o operador navega por dentro dele; a estrutura,
                    o inspector e o caminho da view seguem o slide.
                --}}
                {{--
                    O listener fica FORA do island de propósito: uma ação disparada de
                    um elemento de dentro vira uma chamada escopada ao island e o
                    servidor re-morfaria o deck a cada navegação — exatamente o que o
                    island existe para impedir. O retro-moved borbulha até aqui.
                --}}
            <div class="min-h-0 flex-1" x-on:retro-moved="$wire.selectByIndex($event.detail.index)">
                @island(name: 'deck')
                    {{-- `h-full` e não uma fração de viewport: quem decide a altura é
                         a linha do grid, que já desconta a tira e o cabeçalho. Uma
                         altura em vh voltaria a somar mais do que a tela tem. --}}
                    <div class="retro-embed mx-auto h-full w-full max-w-[var(--builder-max-width)] border border-gray-200 dark:border-white/10">
                        @include('portal::community-retrospective', $this->deck)
                    </div>
                @endisland
            </div>
        </div>

        {{--
            Coluna direita: o estado da edição em cima e o inspector embaixo, os dois
            no mesmo trilho. É a coluna que responde "o que estou editando e o que
            acontece quando eu publicar" — e ela rola por dentro, sem levar a página
            junto.
        --}}
        <div class="flex min-h-0 min-w-0 flex-col gap-3 overflow-y-auto xl:col-start-2 xl:row-span-2">
            <div class="shrink-0">
                <div class="text-xs font-semibold tracking-wide text-gray-400 uppercase dark:text-gray-500">
                    Publicação
                </div>

                <div class="mt-1 flex items-center gap-2">
                    <x-filament::badge :color="$status->getColor()" :icon="$status->getIcon()" size="xs">
                        {{ $status->getLabel() }}
                    </x-filament::badge>
                </div>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $status->getDescription() }}</p>
            </div>

            {{--
                Inspector: contextual, quatro modos, escreve onde a Fase 2 escrevia.
                Sem cabeçalho próprio — a Section de cada modo já nomeia o alvo
                ("Bloco: Discord") e carrega o ícone do modo.
            --}}
            {{ $this->form }}
        </div>

        {{--
            A tira: a estrutura do deck deitada no rodapé, em miniaturas do slide de
            verdade. Embaixo e não à esquerda porque um deck é uma SEQUÊNCIA — uma
            lista vertical em coluna estreita nunca mostrou isso — e porque a coluna
            cobrava do preview a largura que ele mais precisa.

            Sai do catálogo, não da composição: fonte desligada e slide oculto
            continuam aqui, apagados. É nesta tira que mora o botão que os religa; se
            sumissem junto com a composição, não haveria caminho de volta.

            Sem Section em volta: título, descrição e padding da Section custavam
            mais altura do que as próprias miniaturas, e numa tela de altura fixa
            essa altura sai do deck.
        --}}
        <div class="min-w-0 shrink-0 xl:col-start-1 xl:row-start-2">
        <div
            class="mx-auto min-w-0 max-w-[var(--builder-max-width)]"
            x-on:filmstrip-call="$wire.$call($event.detail.method, ...$event.detail.args)"
        >
        @island(name: 'filmstrip')
            {{-- Nome de classe completo, sem import: o corpo da island compila para
                 um arquivo próprio, onde nem o import do topo da view nem um
                 `@use` aqui sobrevivem.

                 E nada de escrever as diretivas de bloco do Blade dentro de um
                 comentário: o compilador casa a diretiva do COMENTÁRIO com o
                 fechamento de verdade e engole o bloco. --}}
            @php
                $deck = $this->deck;
                $groups = $this->filmstrip;
                $closingIndex = $this->closingIndex();
                $promotions = $this->promotionStrip();
                $about = \He4rt\Portal\Retrospective\AboutSection::slides();
            @endphp

            {{-- A largura das miniaturas é decidida AQUI e herdada por todas as
                 células: numa tela de altura fixa a tira é o que sobra depois do
                 deck, então esse número é orçamento de layout, não estilo de célula. --}}
            <div
                class="flex items-start gap-2 overflow-x-auto pb-1"
                style="--retro-thumb-width: 164"
                x-data="{
                    active: @js($this->previewIndex()),
                    focus(index) {
                        if (index === null || index === undefined) return;

                        this.active = index;

                        this.$nextTick(() => {
                            this.$el
                                .querySelector(`[data-deck-index='${index}']`)
                                ?.scrollIntoView({ behavior: 'smooth', block: 'nearest', inline: 'center' });
                        });
                    },
                }"
                x-on:retro-goto.window="focus($event.detail.index)"
                x-on:retro-moved.window="focus($event.detail.index)"
            >
                {{-- Capa: o slide 0, sem fonte e sem on/off. --}}
                <x-panel-admin::retrospective.filmstrip-group :label="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Cover->getLabel()" :icon="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Cover->getIcon()">
                    <x-panel-admin::retrospective.filmstrip-thumb
                        :index="0"
                        :label="$deck['coverKind']->getLabel()"
                        :selection="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Cover->value"
                        :view="\He4rt\Portal\Retrospective\SlideView::cover($deck['coverKind'])"
                        :props="[
                            'since' => $deck['since'],
                            'until' => $deck['until'],
                            'edition' => $deck['edition'],
                            'hosts' => $deck['hosts'],
                            'coverTitle' => $deck['coverTitle'],
                            'coverIntro' => $deck['coverIntro'],
                        ]"
                    />
                </x-panel-admin::retrospective.filmstrip-group>

                {{-- A He4rt: seção fixa do portal. Sem on/off e sem ordem — não é
                     fonte, é o contexto que o deck dá antes dos números. --}}
                <x-panel-admin::retrospective.filmstrip-group :label="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::About->getLabel()" :icon="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::About->getIcon()">
                    @foreach ($about as $position => $slide)
                        <x-panel-admin::retrospective.filmstrip-thumb
                            :index="$position + 1"
                            :label="$slide->label"
                            :selection="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::About->value . ':' . $slide->key"
                            :view="$slide->view()"
                            :props="['sources' => $deck['sources'], 'since' => $deck['since'], 'until' => $deck['until']]"
                        />
                    @endforeach
                </x-panel-admin::retrospective.filmstrip-group>

                @foreach ($groups as $index => $group)
                    <x-panel-admin::retrospective.filmstrip-group
                        :label="$group->label"
                        :group="$group"
                        :first="$index === 0"
                        :last="$index === count($groups) - 1"
                    >
                        @forelse ($group->slides as $slide)
                            <x-panel-admin::retrospective.filmstrip-thumb
                                :index="$slide->index"
                                :label="$slide->label"
                                :muted="! $slide->visible || ! $group->visible"
                                :selection="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Slide->value . ':' . $slide->kind"
                                :view="$slide->view"
                                :props="$slide->props"
                            />
                        @empty
                            {{-- Fonte registrada que não rendeu nada no recorte. O bloco
                                 fica, com o motivo à mostra: some da tira só o que nunca
                                 existiu. --}}
                            <div
                                class="flex aspect-video shrink-0 items-center justify-center rounded-lg border border-dashed border-gray-300 px-2 text-center text-[0.7rem] leading-tight text-gray-400 dark:border-white/10 dark:text-gray-500"
                                style="width: calc(var(--retro-thumb-width, 208) * 1px)"
                            >
                                Sem dado neste recorte
                            </div>
                        @endforelse
                    </x-panel-admin::retrospective.filmstrip-group>
                @endforeach

                {{-- O ritual da tag: posição fixa antes do fecho, então o bloco não
                     tem setas de ordem — só o on/off de cada slide, no inspector. --}}
                <x-panel-admin::retrospective.filmstrip-group :label="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Promotion->getLabel()" :icon="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Promotion->getIcon()">
                    @foreach ($promotions as $slide)
                        <x-panel-admin::retrospective.filmstrip-thumb
                            :index="$slide->index"
                            :label="$slide->label"
                            :muted="! $slide->visible || $slide->view === null"
                            :selection="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Promotion->value . ':' . $slide->kind"
                            :view="$slide->view"
                            :props="$slide->props"
                        />
                    @endforeach
                </x-panel-admin::retrospective.filmstrip-group>

                {{-- Fecho: sempre o último slide do deck. --}}
                <x-panel-admin::retrospective.filmstrip-group :label="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Closing->getLabel()" :icon="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Closing->getIcon()">
                    <x-panel-admin::retrospective.filmstrip-thumb
                        :index="$closingIndex"
                        label="Encerramento"
                        :selection="\He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode::Closing->value"
                    >
                        <x-portal::retro.slides.closing
                            :sources="$deck['sources']"
                            :since="$deck['since']"
                            :until="$deck['until']"
                            :closingText="$deck['closingText']"
                        />
                    </x-panel-admin::retrospective.filmstrip-thumb>
                </x-panel-admin::retrospective.filmstrip-group>
            </div>
        @endisland
            </div>
        </div>
    </div>
</x-filament-panels::page>
