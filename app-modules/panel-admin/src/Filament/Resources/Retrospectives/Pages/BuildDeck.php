<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Concerns\InteractsWithRecord;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Actions as SchemaActions;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Text;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\Community\Retrospective\Contracts\CuratableSource;
use He4rt\Community\Retrospective\DTOs\PromotionEntry;
use He4rt\Community\Retrospective\DTOs\RetrospectiveSnapshot;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Community\Retrospective\Enums\CoverKind;
use He4rt\Community\Retrospective\Enums\ExclusionKind;
use He4rt\Community\Retrospective\Enums\PromotionStage;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Actions\PublishRetrospectiveAction;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\RetrospectiveResource;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\AvailableSources;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\DeckFilmstrip;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\DeckStructure;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\ExclusionPicker;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\FilmstripGroup;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\FilmstripSlide;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorMode;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorSelection;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\InspectorViewPath;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\PromotablePeople;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\SlideEntry;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\SourceBlock;
use He4rt\Portal\Retrospective\AboutSection;
use He4rt\Portal\Retrospective\AboutSlide;
use He4rt\Portal\Retrospective\DeckPresentation;
use He4rt\Portal\Retrospective\PromotionSection;
use He4rt\Portal\Retrospective\PromotionSlide;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;

/**
 * Deck Builder: monta o deck vendo o deck (ADR-0002 do panel-admin). Três colunas
 * — estrutura seleciona, preview só lê, inspector edita — e o inspector escreve
 * exatamente onde o CRUD da Fase 2 escrevia, sem coluna nova.
 *
 * Registrada na chave `edit` do resource com rota `/{record}/deck`: a chave
 * preserva o clique na tabela e o getUrl('edit'), a rota deixa a URL honesta.
 * Não existe uma segunda tela editando `deck_config` — seriam duas fontes de
 * verdade de curadoria.
 *
 * @property-read Schema $form
 */
class BuildDeck extends Page
{
    use InteractsWithRecord;

    /**
     * Token da seleção, tal como vem da wire. `selection()` o reparsa a cada
     * leitura, então um valor inválido degrada para a capa.
     */
    public string $selection = 'cover';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    /**
     * Os slides compostos na ordem do deck, congelados aqui para as ações de
     * seleção não pagarem uma coleta ao vivo por clique: mapear índice <-> seleção
     * só precisa da FORMA do deck, não do dado. Recomputada em mount e a cada
     * salvamento — os únicos momentos em que a forma pode mudar.
     *
     * @var list<array{kind: string, source: string}>
     */
    #[Locked]
    public array $composedKinds = [];

    /**
     * Os slides do ritual da tag que o deck está desenhando, na ordem. Congelado
     * pelo mesmo motivo do composedKinds: mapear índice <-> seleção precisa da
     * FORMA do deck, e resolvê-la a cada clique pagaria uma coleta ao vivo.
     *
     * @var list<string>
     */
    #[Locked]
    public array $promotionKinds = [];

    /**
     * Versão do preview, que entra na key do deck. O `updated_at` sozinho não basta:
     * dois salvamentos no mesmo segundo dariam a mesma key e o Livewire morfaria o
     * deck em vez de recriá-lo, deixando o Alpine com a lista de slides antiga.
     */
    public int $previewVersion = 0;

    protected static string $resource = RetrospectiveResource::class;

    protected string $view = 'panel-admin::retrospective.build-deck';

    /**
     * Largura cheia: as três colunas do ADR-0002 (estrutura, deck, inspector) não
     * caem bem no 7xl padrão do painel — o preview do meio é um deck inteiro, não um
     * card. Só esta página; o resto do painel segue no default.
     */
    protected Width|string|null $maxContentWidth = Width::Full;

    /**
     * Memo do snapshot desta requisição. Privada: o Livewire só serializa
     * propriedades públicas, então ela nasce vazia a cada roundtrip — que é
     * exatamente a vida útil que um snapshot ao vivo pode ter.
     */
    private ?RetrospectiveSnapshot $deckSnapshot = null;

    public function mount(int|string $record): void
    {
        $this->record = $this->resolveRecord($record);

        $this->composedKinds = $this->composeKinds();
        $this->promotionKinds = $this->composePromotionKinds();

        $this->fillInspector();
    }

    public function getTitle(): string
    {
        return $this->getRetrospective()->title;
    }

    /**
     * Quantos slides o deck tem, contando capa, a seção fixa e o fecho. É o
     * denominador do "4 / 12" da barra do preview.
     */
    public function slideTotal(): int
    {
        return $this->closingIndex() + 1;
    }

    /**
     * Onde começa o ritual da tag: depois de todos os slides de fonte. Dono único
     * do deslocamento do fim do deck, como composedOffset é do começo.
     */
    public function promotionOffset(): int
    {
        return $this->composedOffset() + count($this->composedKinds);
    }

    public function closingIndex(): int
    {
        return $this->promotionOffset() + count($this->promotionKinds);
    }

    /**
     * Quantos slides o deck desenha ANTES do primeiro slide composto: a capa e a
     * seção fixa sobre a He4rt. Dono único do deslocamento — índice errado aqui
     * manda o preview para o slide vizinho a cada clique na tira, e a contagem da
     * seção mora no portal, que é quem a desenha.
     */
    public function composedOffset(): int
    {
        return 1 + AboutSection::count();
    }

    /**
     * O que está selecionado, em palavras, para a barra fina acima do preview.
     * Slide vem prefixado pela fonte ("GitHub / Destaques"): sozinho, um rótulo
     * como "Panorama" não diz de quem ele é.
     */
    public function selectionLabel(): string
    {
        $selection = $this->selection();

        return match ($selection->mode) {
            InspectorMode::Cover, InspectorMode::Closing => $selection->mode->getLabel(),
            InspectorMode::About => $this->aboutLabel($selection->requireTarget()),
            InspectorMode::Source => $this->sourceLabel($selection->requireTarget()),
            InspectorMode::Slide => $this->slideLabelWithSource($selection->requireTarget()),
            InspectorMode::Promotion => $this->promotionLabel($selection->requireTarget()),
        };
    }

    /**
     * Relê o registro para a UI acompanhar o job de publicação. Chamado pelo poll da
     * view: sem isso o operador fica olhando "Publicando" até recarregar na mão.
     */
    public function refreshStatus(): void
    {
        $this->getRetrospective()->refresh();
    }

    /**
     * Seleciona um alvo na coluna de estrutura e recarrega o inspector no modo
     * correspondente.
     */
    public function select(string $selection): void
    {
        $this->selection = InspectorSelection::parse($selection)->token();

        $this->fillInspector();

        $this->showSelectedSlide();
    }

    /**
     * Caminho inverso do select(): o operador navegou DENTRO do deck (setas, dots,
     * teclado) e a estrutura, o inspector e o caminho da view acompanham.
     *
     * Não devolve o deck para lugar nenhum — ele já está no slide certo, foi ele
     * quem avisou. Chamar showSelectedSlide() aqui reabriria o ciclo.
     */
    public function selectByIndex(int $index): void
    {
        $selection = $this->selectionAtIndex($index);

        if ($selection->token() === $this->selection) {
            return;
        }

        $this->selection = $selection->token();

        $this->fillInspector();
    }

    /**
     * Reordena por botão (subir/descer). Persiste a ordem INTEIRA da timeline, não
     * só o par trocado — é o que ancora as fontes que ainda não estavam em `order`.
     */
    public function moveSource(string $key, int $offset): void
    {
        $record = $this->getRetrospective();

        $record->update([
            'deck_config' => $record->deck_config->withOrder(
                DeckStructure::moved($this->blocks(), $key, $offset),
            ),
        ]);

        $this->refreshPreview();
    }

    /**
     * Liga/desliga uma fonte direto na tira, sem passar pelo inspector. Curadoria
     * de apresentação: re-deriva do snapshot na composição, sem republicar.
     */
    public function toggleSource(string $key): void
    {
        $record = $this->getRetrospective();

        $record->update([
            'deck_config' => $record->deck_config->withSourceVisible(
                $key,
                !$record->deck_config->showsSource($key),
            ),
        ]);

        $this->refreshPreview();

        // O inspector pode estar mostrando ESTA fonte, com o toggle no estado
        // velho: recarrega para os dois concordarem.
        $this->fillInspector();
    }

    public function save(): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->form->getState();

        $record = $this->getRetrospective();
        $selection = $this->selection();

        match ($selection->mode) {
            InspectorMode::Cover => $this->saveCover($record, $data),
            // Sem campo, sem escrita. O botão Salvar nem aparece neste modo
            // (InspectorMode::editable), então só se chega aqui por wire forjada.
            InspectorMode::About => null,
            InspectorMode::Closing => $this->saveClosing($record, $data),
            InspectorMode::Source => $this->saveSource($record, $selection->requireTarget(), $data),
            InspectorMode::Slide => $this->saveSlide($record, $selection->requireTarget(), $data),
            InspectorMode::Promotion => $this->savePromotion($record, $selection->requireTarget(), $data),
        };

        $this->refreshPreview();

        Notification::make()
            ->success()
            ->title('Deck salvo')
            ->send();
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make($this->inspectorComponents())
                    ->livewireSubmitHandler('save')
                    ->footer([
                        SchemaActions::make([
                            Action::make('save')
                                ->label('Salvar')
                                ->icon(Heroicon::OutlinedCheck)
                                ->submit('save')
                                ->keyBindings(['mod+s'])
                                ->visible(fn (): bool => $this->selection()->mode->editable()),
                        ]),
                    ]),
            ])
            ->statePath('data');
    }

    /**
     * Timeline da coluna de estrutura, recomputada a cada render (só label() e
     * slideCatalog(), ambos sem tocar o banco).
     *
     * @return list<SourceBlock>
     */
    public function blocks(): array
    {
        return DeckStructure::blocks($this->getRetrospective()->deck_config);
    }

    public function selection(): InspectorSelection
    {
        return InspectorSelection::parse($this->selection);
    }

    /**
     * O arquivo blade do que está selecionado, relativo à raiz do projeto. Fica no
     * cabeçalho do preview para encurtar o caminho entre "não gostei disso" e o
     * editor aberto no arquivo certo. Null quando a seleção não tem view própria
     * (bloco de fonte) ou quando o kind não tem partial.
     */
    public function viewPath(): ?string
    {
        return InspectorViewPath::for($this->selection(), $this->getRetrospective()->cover_kind);
    }

    /**
     * As props do deck, montadas pelo MESMO DeckPresentation da página pública —
     * mesmo ComposeDeck, mesmas partials. Preview que mente é pior que preview
     * nenhum, e a garantia de que não mente é dividir o caminho de render, não
     * estar num iframe.
     *
     * `live: true` coleta as fontes na hora enquanto a edição é rascunho, então o
     * operador vê o que SERÁ publicado.
     *
     * @return array<string, mixed>
     */
    #[Computed]
    public function deck(): array
    {
        $props = DeckPresentation::fromSnapshot($this->getRetrospective(), $this->deckSnapshot());

        // A key carrega a versão: ao salvar, o Livewire recria o deck em vez de
        // morfá-lo, e o Alpine reinicializa com a nova lista de slides.
        $props['stateKey'] = $props['stateKey'].'-'.$this->previewVersion;

        return $props;
    }

    /**
     * O snapshot desta edição, resolvido UMA vez por render. Em rascunho ele é
     * coletado ao vivo — dezenas de queries —, e o deck e a tira o dividem.
     *
     * Memoizado numa propriedade privada, e não com #[Computed]: o cache do
     * #[Computed] só existe no acesso mágico `$this->deckSnapshot`, que é intipável
     * (a análise estática não vê a propriedade e o retorno vira mixed). Aqui o
     * memo é explícito, o tipo sobrevive, e não há a armadilha de chamar o método
     * e pagar a coleta de novo em silêncio.
     */
    public function deckSnapshot(): RetrospectiveSnapshot
    {
        return $this->deckSnapshot ??= DeckPresentation::snapshotFor($this->getRetrospective(), live: true);
    }

    /**
     * A tira de miniaturas do rodapé. Sai do snapshot CRU, não da composição: o
     * que está desligado continua na tira, apagado, porque é lá que mora o botão
     * que o religa.
     *
     * @return list<FilmstripGroup>
     */
    #[Computed]
    public function filmstrip(): array
    {
        return DeckFilmstrip::groups(
            $this->deckSnapshot(),
            $this->getRetrospective()->deck_config,
            $this->composedKinds,
            $this->composedOffset(),
        );
    }

    /**
     * As miniaturas do ritual da tag, no mesmo formato dos slides de fonte.
     *
     * Sai do CATÁLOGO e do snapshot cru, como o resto da tira: um slide desligado
     * continua aqui, apagado, porque é nesta célula que mora o caminho de volta.
     * Sem ninguém escolhido não há miniatura — mas o cartão fica, com o rótulo
     * dizendo que o slide existe e está vazio.
     *
     * @return list<FilmstripSlide>
     */
    public function promotionStrip(): array
    {
        $cards = $this->deckSnapshot()->promotions;
        $config = $this->getRetrospective()->deck_config;

        return array_map(
            function (PromotionSlide $slide) use ($cards, $config): FilmstripSlide {
                $filled = $slide->withCards(PromotionSection::cardsFor($cards, $slide->stage));
                $position = array_search($slide->kind, $this->promotionKinds, strict: true);

                return new FilmstripSlide(
                    kind: $slide->kind,
                    label: $slide->label,
                    visible: $config->showsSlide($slide->kind),
                    view: $filled->isEmpty() ? null : $filled->view(),
                    props: ['cards' => $filled->cards],
                    index: $position === false ? null : $this->promotionOffset() + $position,
                );
            },
            PromotionSection::catalog(),
        );
    }

    /**
     * Em que slide o preview deve parar para mostrar o que está selecionado. O deck
     * renderiza capa, os slides na ordem composta e o fecho — então a posição sai da
     * própria composição, sem o markup precisar anunciar o kind.
     */
    public function previewIndex(): int
    {
        return $this->previewTarget() ?? 0;
    }

    /**
     * O mesmo índice, mas honesto sobre a ausência: null quando o que está
     * selecionado NÃO está no deck — slide desligado, kind sem dado no recorte,
     * ritual sem ninguém escolhido.
     *
     * A distinção existe por causa do clique numa miniatura vazia: tratar isso
     * como "índice 0" mandava o preview e a tira de volta para a capa, arrastando
     * o operador para longe justamente quando ele foi preencher o slide.
     */
    public function previewTarget(): ?int
    {
        $kinds = $this->composedKinds;

        if ($kinds === []) {
            return null;
        }

        $selection = $this->selection();

        return match ($selection->mode) {
            InspectorMode::Cover => 0,
            InspectorMode::About => $this->aboutIndex($selection->requireTarget()),
            // O fecho é o último slide, depois de tudo.
            InspectorMode::Closing => $this->closingIndex(),
            InspectorMode::Promotion => $this->promotionIndex($selection->requireTarget()),
            InspectorMode::Slide => $this->slideIndex($kinds, fn (array $slide): bool => $slide['kind'] === $selection->requireTarget()),
            InspectorMode::Source => $this->slideIndex($kinds, fn (array $slide): bool => $slide['source'] === $selection->requireTarget()),
        };
    }

    /**
     * Aponta para a rota pública de preview, para abrir o deck em tela cheia noutra aba.
     */
    public function previewUrl(): string
    {
        return route('community.retrospective.preview', $this->getRetrospective());
    }

    public function getRetrospective(): Retrospective
    {
        /** @var Retrospective $record */
        $record = $this->getRecord();

        return $record;
    }

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            PublishRetrospectiveAction::make()
                ->record(fn (): Retrospective => $this->getRetrospective()),

            Action::make('preview')
                ->label('Abrir preview')
                ->icon(Heroicon::OutlinedArrowTopRightOnSquare)
                ->color('gray')
                ->url(fn (): string => route('community.retrospective.preview', $this->getRetrospective()))
                ->openUrlInNewTab(),

            DeleteAction::make()
                ->record(fn (): Retrospective => $this->getRetrospective())
                ->successRedirectUrl(RetrospectiveResource::getUrl('index')),
        ];
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, static fn (mixed $item): bool => is_string($item) && $item !== ''));
    }

    /**
     * O Radio devolve o enum quando o estado veio do fill e a string quando veio
     * da wire; os dois caminhos passam por aqui para o resto não decidir.
     */
    private function coverKindFrom(mixed $state): CoverKind
    {
        if ($state instanceof CoverKind) {
            return $state;
        }

        return CoverKind::tryFrom((string) $state) ?? CoverKind::Retrospective;
    }

    /**
     * Que seleção corresponde a um slide do deck — o inverso de previewIndex().
     *
     * Um kind pode render vários slides (github.repos é um card por repositório),
     * então índices diferentes caem na mesma seleção: a curadoria é por kind.
     */
    private function selectionAtIndex(int $index): InspectorSelection
    {
        $kinds = $this->composedKinds;

        if ($index <= 0 || $kinds === []) {
            return InspectorSelection::cover();
        }

        $about = AboutSection::slides()[$index - 1] ?? null;

        if ($about !== null) {
            return new InspectorSelection(InspectorMode::About, $about->key);
        }

        // A capa e a seção fixa ocupam o começo do deck; o resto são os compostos.
        $slide = $kinds[$index - $this->composedOffset()] ?? null;

        if ($slide !== null) {
            return new InspectorSelection(InspectorMode::Slide, $slide['kind']);
        }

        $promotion = $this->promotionKinds[$index - $this->promotionOffset()] ?? null;

        return $promotion === null
            ? new InspectorSelection(InspectorMode::Closing)
            : new InspectorSelection(InspectorMode::Promotion, $promotion);
    }

    /**
     * Os slides do ritual que o deck está desenhando, na ordem. Sai do MESMO
     * PromotionSection que o portal usa para desenhar — se o painel recontasse a
     * regra de "tem gente e está ligado", uma divergência mandaria o preview para
     * o slide errado.
     *
     * @return list<string>
     */
    private function composePromotionKinds(): array
    {
        /** @var list<PromotionSlide> $slides */
        $slides = $this->deck()['promotions'];

        return array_map(static fn (PromotionSlide $slide): string => $slide->kind, $slides);
    }

    /**
     * Onde o slide do ritual caiu no deck. Um kind fora da composição (desligado,
     * ou sem ninguém escolhido) não tem posição: a capa é o fallback honesto,
     * igual ao slideIndex().
     */
    private function promotionIndex(string $kind): ?int
    {
        $position = array_search($kind, $this->promotionKinds, strict: true);

        return $position === false ? null : $this->promotionOffset() + $position;
    }

    private function promotionLabel(string $kind): string
    {
        $slide = PromotionSection::find($kind);

        return $slide instanceof PromotionSlide
            ? InspectorMode::Promotion->getLabel().' / '.$slide->label
            : InspectorMode::Promotion->getLabel();
    }

    /**
     * O estágio que o slide selecionado edita. Um kind desconhecido (token velho
     * na wire) cai em destaque — o estágio que não entrega tag a ninguém.
     */
    private function promotionStage(string $kind): PromotionStage
    {
        return PromotionSection::find($kind)->stage ?? PromotionStage::Spotlight;
    }

    /**
     * Inspector do ritual: o on/off do slide e a lista de pessoas daquele estágio.
     *
     * O estágio vem do SLIDE, não de um campo: no slide de destaques o operador
     * edita destaques, no da tag edita quem recebeu. Um seletor de estágio dentro
     * da lista permitiria mover alguém para um slide que não está na tela.
     *
     * @return array<int, Section>
     */
    private function promotionComponents(string $kind): array
    {
        $slide = PromotionSection::find($kind);
        $stage = $this->promotionStage($kind);

        return [
            Section::make($slide instanceof PromotionSlide ? $slide->label : InspectorMode::Promotion->getLabel())
                ->compact()
                ->icon($stage->getIcon())
                ->description($slide instanceof PromotionSlide ? $slide->hint : $stage->getDescription())
                ->schema([
                    Toggle::make('visible')
                        ->label('Exibir no deck')
                        ->helperText('Sem ninguém na lista o slide não é desenhado, mesmo ligado.'),

                    Repeater::make('people')
                        ->label($stage->getLabel())
                        ->helperText('Os números saem do recorte; aqui só se escolhe quem aparece e por quê.')
                        ->addActionLabel('Adicionar pessoa')
                        ->reorderable()
                        ->collapsible()
                        ->itemLabel(function (array $state): ?string {
                            $userId = $state['user_id'] ?? null;

                            return is_string($userId) ? PromotablePeople::labelFor($userId) : null;
                        })
                        ->schema([
                            Select::make('user_id')
                                ->label('Pessoa')
                                ->required()
                                ->distinct()
                                ->searchable()
                                ->helperText('Só aparece quem tem Discord ou GitHub vinculado — sem conta não há número para mostrar.')
                                ->getSearchResultsUsing(fn (string $search): array => PromotablePeople::search($search))
                                ->getOptionLabelUsing(fn (?string $value): ?string => PromotablePeople::labelFor($value)),

                            TextInput::make('reason')
                                ->label('Motivo')
                                ->placeholder('segurou o #ajuda o ano inteiro')
                                ->maxLength(160),
                        ]),
                ]),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function savePromotion(Retrospective $record, string $kind, array $data): void
    {
        $stage = $this->promotionStage($kind);
        $config = $record->deck_config->withSlideVisible($kind, (bool) ($data['visible'] ?? true));

        // Compara ASSINATURAS e não os DTOs: são objetos novos a cada leitura do
        // jsonb, então `!==` seria sempre verdadeiro e o aviso de republicar
        // apareceria mesmo quando nada mudou.
        $before = $this->signaturesOf($config->promotionsFor($stage));
        $config = $config->withPromotionsFor($stage, $this->submittedPeople($stage, $data));

        $record->update(['deck_config' => $config]);

        if ($this->signaturesOf($config->promotionsFor($stage)) !== $before) {
            $this->warnAboutPromotions();
        }
    }

    /**
     * @param  list<PromotionEntry>  $entries
     * @return list<string>
     */
    private function signaturesOf(array $entries): array
    {
        return array_map(
            static fn (PromotionEntry $entry): string => $entry->signature(),
            $entries,
        );
    }

    /**
     * @param  array<string, mixed>  $data
     * @return list<PromotionEntry>
     */
    private function submittedPeople(PromotionStage $stage, array $data): array
    {
        $rows = is_array($data['people'] ?? null) ? $data['people'] : [];
        $entries = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $entry = PromotionEntry::makeFromPayload([...$row, 'stage' => $stage->value]);

            // Linha em branco (o Repeater cria uma antes de o operador escolher
            // alguém) some em silêncio: não é erro, é uma escolha inacabada.
            if ($entry instanceof PromotionEntry) {
                $entries[] = $entry;
            }
        }

        return $entries;
    }

    private function warnAboutPromotions(): void
    {
        Notification::make()
            ->warning()
            ->title('Lista da tag alterada')
            ->body('Os números de quem aparece são medidos no recorte. Republique a edição para recompilar o snapshot.')
            ->persistent()
            ->send();
    }

    /**
     * Os slides compostos, achatados na ordem em que o deck os renderiza. Caro
     * (passa pelo deck completo): só mount e refreshPreview chamam; o resto lê
     * a propriedade congelada.
     *
     * @return list<array{kind: string, source: string}>
     */
    private function composeKinds(): array
    {
        $kinds = [];

        /** @var list<SourceResult> $sources */
        $sources = $this->deck()['sources'];

        foreach ($sources as $source) {
            foreach ($source->slides as $slide) {
                $kinds[] = ['kind' => $slide->kind(), 'source' => $source->key];
            }
        }

        return $kinds;
    }

    /**
     * @param  list<array{kind: string, source: string}>  $kinds
     * @param  callable(array{kind: string, source: string}): bool  $matches
     */
    private function slideIndex(array $kinds, callable $matches): ?int
    {
        foreach ($kinds as $position => $slide) {
            if ($matches($slide)) {
                return $position + $this->composedOffset();
            }
        }

        // Selecionado algo que o deck não está mostrando (desligado, ou sem dado
        // no recorte): não há para onde levar o preview.
        return null;
    }

    /**
     * @return array<int, Section>
     */
    private function inspectorComponents(): array
    {
        $selection = $this->selection();

        return match ($selection->mode) {
            InspectorMode::Cover => $this->coverComponents(),
            InspectorMode::About => $this->aboutComponents($selection->requireTarget()),
            InspectorMode::Closing => $this->closingComponents(),
            InspectorMode::Source => $this->sourceComponents($selection->requireTarget()),
            InspectorMode::Slide => $this->slideComponents($selection->requireTarget()),
            InspectorMode::Promotion => $this->promotionComponents($selection->requireTarget()),
        };
    }

    /**
     * O rótulo do slide fixo, prefixado pela seção — sozinho, "Onde acontece" não
     * diz de onde ele saiu. Chave desconhecida (seleção velha, slide removido do
     * portal) cai no nome da seção em vez de mentir um rótulo.
     */
    private function aboutLabel(string $key): string
    {
        $slide = AboutSection::find($key);

        return $slide instanceof AboutSlide
            ? InspectorMode::About->getLabel().' / '.$slide->label
            : InspectorMode::About->getLabel();
    }

    /**
     * Onde o slide fixo caiu no deck: logo depois da capa, na ordem da seção.
     */
    private function aboutIndex(string $key): ?int
    {
        $position = AboutSection::positionOf($key);

        return $position === null ? null : $position + 1;
    }

    /**
     * Inspector da seção fixa: só diz o que ela é e por que não há campo aqui. O
     * caminho da view, que é o que o operador precisa para mexer nela, já fica na
     * barra do preview (InspectorViewPath).
     *
     * @return array<int, Section>
     */
    private function aboutComponents(string $key): array
    {
        $slide = AboutSection::find($key);

        return [
            Section::make($slide instanceof AboutSlide ? $slide->label : InspectorMode::About->getLabel())
                ->compact()
                ->icon(InspectorMode::About->getIcon())
                ->description(InspectorMode::About->getDescription())
                ->schema([
                    Text::make('Quem a He4rt é não muda a cada recorte, então esta seção não vem do snapshot e nenhuma curadoria a desliga. O texto mora no blade acima; os números saem do período da edição.'),
                ]),
        ];
    }

    /**
     * @return array<int, Section>
     */
    private function coverComponents(): array
    {
        return [
            Section::make('Capa')
                ->compact()
                ->icon(InspectorMode::Cover->getIcon())
                ->description('Só texto editorial e recorte; números, avatares e período exibido são computados à parte.')
                ->schema([
                    TextInput::make('title')
                        ->label('Título da edição')
                        ->required()
                        ->maxLength(255),

                    DateTimePicker::make('since')
                        ->label('Início do período')
                        ->seconds(condition: false)
                        ->timezone(config('app.display_timezone'))
                        ->required(),

                    DateTimePicker::make('until')
                        ->label('Fim do período')
                        ->seconds(condition: false)
                        ->timezone(config('app.display_timezone'))
                        ->required(),

                    Toggle::make('hide_bots')
                        ->label('Ocultar bots')
                        ->helperText('Mexe nos números: republique para valer.'),

                    Radio::make('cover_kind')
                        ->label('Tipo de capa')
                        ->options(CoverKind::class)
                        ->required()
                        ->live(),

                    Select::make('hosts')
                        ->label('Quem apresenta')
                        ->multiple()
                        ->searchable()
                        ->helperText('Aparecem na capa, nesta ordem, com nome e avatar. Só quem tem Discord ou GitHub vinculado, para o avatar existir.')
                        ->getSearchResultsUsing(fn (string $search): array => PromotablePeople::search($search))
                        ->getOptionLabelsUsing(fn (array $values): array => PromotablePeople::labelsFor($values))
                        ->visible(fn (Get $get): bool => $this->coverKindFrom($get('cover_kind'))->isOnboarding()),

                    TextInput::make('cover_title')
                        ->label('Título da capa')
                        ->maxLength(255),

                    Textarea::make('cover_intro')
                        ->label('Introdução da capa')
                        ->rows(3),
                ]),
        ];
    }

    /**
     * @return array<int, Section>
     */
    private function closingComponents(): array
    {
        return [
            Section::make('Fecho')
                ->compact()
                ->icon(InspectorMode::Closing->getIcon())
                ->description('A última palavra do deck.')
                ->schema([
                    Textarea::make('closing_text')
                        ->label('Mensagem de fecho')
                        ->rows(4),
                ]),
        ];
    }

    /**
     * Inspector de um bloco de fonte. O picker só aparece para quem implementa
     * CuratableSource — a fonte crua fica com ordem e on/off, e o deck segue
     * montando (ISP, ADR-0002).
     *
     * @return array<int, Section>
     */
    private function sourceComponents(string $key): array
    {
        $sections = [
            Section::make('Bloco: '.$this->sourceLabel($key))
                ->compact()
                ->icon(InspectorMode::Source->getIcon())
                ->description('Desligar re-deriva do snapshot na composição, sem republicar.')
                ->schema([
                    Toggle::make('visible')
                        ->label('Exibir no deck'),
                ]),
        ];

        $source = $this->curatableSource($key);

        if (!$source instanceof CuratableSource) {
            return $sections;
        }

        $picker = $this->picker($source);

        $sections[] = Section::make('Exclusions')
            ->compact()
            ->icon(Heroicon::OutlinedEyeSlash)
            ->description('Esconde um item ou pessoa desta fonte. Mexe no DADO: sai dos slides e também dos números, então exige republicar para valer.')
            ->schema([
                CheckboxList::make('exclusion_items')
                    ->label(ExclusionKind::Item->getLabel().'s escondidos')
                    ->helperText(ExclusionKind::Item->getDescription())
                    ->options($picker->options(ExclusionKind::Item))
                    ->descriptions($picker->descriptions(ExclusionKind::Item))
                    ->searchable()
                    ->bulkToggleable()
                    ->visible($picker->hasOptions(ExclusionKind::Item)),

                CheckboxList::make('exclusion_people')
                    ->label(ExclusionKind::Person->getLabel().'s escondidas')
                    ->helperText(ExclusionKind::Person->getDescription())
                    ->options($picker->options(ExclusionKind::Person))
                    ->descriptions($picker->descriptions(ExclusionKind::Person))
                    ->searchable()
                    ->bulkToggleable()
                    ->visible($picker->hasOptions(ExclusionKind::Person)),
            ]);

        return $sections;
    }

    /**
     * @return array<int, Section>
     */
    private function slideComponents(string $kind): array
    {
        $entry = $this->slideEntry($kind);

        return [
            // O kind pode não estar em catálogo nenhum (token velho vindo da wire);
            // nesse caso o próprio kind serve de rótulo.
            Section::make('Slide: '.($entry->label ?? $kind))
                ->compact()
                ->icon(InspectorMode::Slide->getIcon())
                ->description($entry->hint ?? InspectorMode::Slide->getDescription())
                ->schema([
                    Toggle::make('visible')
                        ->label('Exibir no deck')
                        ->helperText('O toggle vale para o kind inteiro — "'.$kind.'" pode render mais de um slide.'),
                ]),
        ];
    }

    private function fillInspector(): void
    {
        $this->form->fill($this->inspectorState());
    }

    /**
     * @return array<string, mixed>
     */
    private function inspectorState(): array
    {
        $record = $this->getRetrospective();
        $config = $record->deck_config;
        $selection = $this->selection();

        return match ($selection->mode) {
            InspectorMode::Cover => [
                'title' => $record->title,
                'since' => $record->since,
                'until' => $record->until,
                'hide_bots' => $record->hide_bots,
                'cover_kind' => $record->cover_kind->value,
                'hosts' => $config->hosts,
                'cover_title' => $record->cover_title,
                'cover_intro' => $record->cover_intro,
            ],
            // Nada para preencher: a seção fixa não tem campo nenhum.
            InspectorMode::About => [],
            InspectorMode::Closing => [
                'closing_text' => $record->closing_text,
            ],
            InspectorMode::Source => [
                'visible' => $config->showsSource($selection->requireTarget()),
                ...$this->exclusionState($selection->requireTarget()),
            ],
            InspectorMode::Slide => [
                'visible' => $config->showsSlide($selection->requireTarget()),
            ],
            InspectorMode::Promotion => [
                'visible' => $config->showsSlide($selection->requireTarget()),
                'people' => array_map(
                    static fn (PromotionEntry $entry): array => [
                        'user_id' => $entry->userId,
                        'reason' => $entry->reason,
                    ],
                    $config->promotionsFor($this->promotionStage($selection->requireTarget())),
                ),
            ],
        };
    }

    /**
     * @return array<string, list<string>>
     */
    private function exclusionState(string $key): array
    {
        $source = $this->curatableSource($key);

        if (!$source instanceof CuratableSource) {
            return [];
        }

        $picker = $this->picker($source);
        $excluded = $this->getRetrospective()->deck_config->exclusionsFor($key);

        return [
            'exclusion_items' => $picker->selected(ExclusionKind::Item, $excluded),
            'exclusion_people' => $picker->selected(ExclusionKind::Person, $excluded),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveCover(Retrospective $record, array $data): void
    {
        $coverKind = $this->coverKindFrom($data['cover_kind'] ?? null);

        $record->update([
            'title' => $data['title'],
            'since' => $data['since'],
            'until' => $data['until'],
            'hide_bots' => (bool) ($data['hide_bots'] ?? false),
            'cover_kind' => $coverKind,
            'cover_title' => $data['cover_title'] ?? null,
            'cover_intro' => $data['cover_intro'] ?? null,
            // Apresentadores só fazem sentido no onboarding: trocar a capa de
            // volta limpa a lista em vez de deixar nomes escondidos esperando.
            'deck_config' => $record->deck_config->withHosts(
                $coverKind->isOnboarding() ? $this->stringList($data['hosts'] ?? []) : [],
            ),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveClosing(Retrospective $record, array $data): void
    {
        $record->update(['closing_text' => $data['closing_text'] ?? null]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveSource(Retrospective $record, string $key, array $data): void
    {
        $config = $record->deck_config->withSourceVisible($key, (bool) ($data['visible'] ?? true));

        $source = $this->curatableSource($key);

        if ($source instanceof CuratableSource) {
            $before = $config->exclusionsFor($key);
            $config = $config->withExclusionsFor($key, $this->submittedRefs($source, $key, $data));

            if ($config->exclusionsFor($key) !== $before) {
                $this->warnAboutRepublishing();
            }
        }

        $record->update(['deck_config' => $config]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function saveSlide(Retrospective $record, string $kind, array $data): void
    {
        $record->update([
            'deck_config' => $record->deck_config->withSlideVisible($kind, (bool) ($data['visible'] ?? true)),
        ]);
    }

    /**
     * Os refs marcados nos dois checkbox lists MAIS os que o picker não conseguiu
     * exibir. Sem os órfãos, desmarcar qualquer coisa apagaria por omissão o que
     * ficou fora do teto da varredura.
     *
     * @param  array<string, mixed>  $data
     * @return list<string>
     */
    private function submittedRefs(CuratableSource $source, string $key, array $data): array
    {
        $picker = $this->picker($source);

        return [
            ...$this->refList($data['exclusion_items'] ?? []),
            ...$this->refList($data['exclusion_people'] ?? []),
            ...$picker->orphans($this->getRetrospective()->deck_config->exclusionsFor($key)),
        ];
    }

    /**
     * @return list<string>
     */
    private function refList(mixed $value): array
    {
        if (!is_array($value)) {
            return [];
        }

        return array_values(array_filter($value, is_string(...)));
    }

    private function warnAboutRepublishing(): void
    {
        Notification::make()
            ->warning()
            ->title('Exclusion alterada')
            ->body('Exclusion mexe nos números. Republique a edição para recompilar o snapshot.')
            ->persistent()
            ->send();
    }

    /**
     * A fonte, se ela souber se curar. Devolver null é o caminho legítimo da fonte
     * crua, não um erro.
     */
    private function curatableSource(string $key): ?CuratableSource
    {
        $source = AvailableSources::all()[$key] ?? null;

        return $source instanceof CuratableSource ? $source : null;
    }

    private function picker(CuratableSource $source): ExclusionPicker
    {
        return ExclusionPicker::for($source, $this->getRetrospective()->period());
    }

    private function sourceLabel(string $key): string
    {
        return AvailableSources::map()[$key] ?? $key;
    }

    /**
     * "Fonte / Slide". A fonte sai do composedKinds — é lá que mora o par
     * (kind, source) do deck renderizado —, e um kind fora da composição degrada
     * para o rótulo sozinho em vez de inventar uma fonte.
     */
    private function slideLabelWithSource(string $kind): string
    {
        $label = $this->slideEntry($kind)->label ?? $kind;

        foreach ($this->composedKinds as $slide) {
            if ($slide['kind'] === $kind) {
                return $this->sourceLabel($slide['source']).' / '.$label;
            }
        }

        return $label;
    }

    private function slideEntry(string $kind): ?SlideEntry
    {
        foreach ($this->blocks() as $block) {
            foreach ($block->slides as $slide) {
                if ($slide->kind === $kind) {
                    return $slide;
                }
            }
        }

        return null;
    }

    /**
     * Custo aceito do ADR: o deck é recomposto inteiro em vez de atualizar o slide
     * no lugar. Uma coleta ao vivo por salvamento em rascunho, para um operador.
     */
    private function refreshPreview(): void
    {
        $this->getRetrospective()->refresh();

        $this->deckSnapshot = null;

        unset($this->deck, $this->filmstrip);

        $this->composedKinds = $this->composeKinds();
        $this->promotionKinds = $this->composePromotionKinds();

        $this->previewVersion++;

        // O deck vive num island e não acompanha os renders da página; depois de um
        // salvamento é o único momento em que ele PRECISA re-renderizar.
        $this->renderIsland('deck');

        // A tira mostra o mesmo deck em miniatura e vive no mesmo regime de island:
        // se não for redesenhada aqui, um slide ligado agora continuaria apagado.
        $this->renderIsland('filmstrip');

        // A key mudou, o deck é recriado e volta para a capa: leva o operador de
        // volta ao que ele estava editando.
        $this->showSelectedSlide();
    }

    /**
     * Manda o deck embutido parar no slide da seleção atual. Efeito de cliente
     * depois da resposta: o índice muda a cada render, então mora aqui em vez de
     * num x-data, que só seria avaliado na primeira vez.
     *
     * O rAF espera o Alpine reinicializar quando o deck acabou de ser recriado —
     * sem ele, o go(0) do init() rodaria depois deste salto.
     */
    private function showSelectedSlide(): void
    {
        $index = $this->previewTarget();

        // Nada a mostrar: o operador clicou num slide que o deck não desenha. O
        // preview fica onde está — mandá-lo para a capa desfaria o scroll da tira
        // no exato momento em que ele foi preencher aquele slide.
        if ($index === null) {
            return;
        }

        $this->js(sprintf(
            "requestAnimationFrame(() => window.dispatchEvent(new CustomEvent('retro-goto', { detail: { index: %d } })))",
            $index,
        ));
    }
}
