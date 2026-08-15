<div>
    <livewire:hero-section />
    {{-- Panorama de contribuições pronto e testado, fora do ar até o desenho da
         seção ser aprovado: <x-portal::sections.contributions :panorama="..." /> --}}
    <x-portal::sections.latest-articles :articles="$latestArticles" />
    <livewire:upcoming-events-section />
</div>
