<?php

declare(strict_types=1);

namespace He4rt\Events\Gallery\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Facades\DB;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * MorphMany para a tabela `media` a partir de um dono com chave UUID.
 *
 * A tabela do media library guarda `model_id` como texto para servir a
 * qualquer tipo de chave. Carregar a relação funciona, porque o valor vai
 * como parâmetro. Já `whereHas` e `withCount` comparam coluna com coluna, e
 * o Postgres não tem operador `uuid = varchar`. Aqui a chave do dono ganha o
 * cast para texto nessa comparação.
 *
 * @template TRelatedModel of Media
 * @template TDeclaringModel of \Illuminate\Database\Eloquent\Model
 *
 * @extends MorphMany<TRelatedModel, TDeclaringModel>
 */
final class PhotosRelation extends MorphMany
{
    /**
     * @param  Builder<TRelatedModel>  $query
     * @param  Builder<TDeclaringModel>  $parentQuery
     * @param  array<int|string, mixed>  $columns
     * @return Builder<TRelatedModel>
     */
    public function getRelationExistenceQuery(Builder $query, Builder $parentQuery, $columns = ['*']): Builder
    {
        $grammar = $query->getQuery()->getGrammar();

        $parentKeyAsText = DB::raw($grammar->wrap($this->getQualifiedParentKeyName()).'::text');

        return $query
            ->select($columns)
            ->whereColumn($parentKeyAsText, '=', $this->getExistenceCompareKey())
            ->where($query->qualifyColumn($this->getMorphType()), $this->getMorphClass());
    }
}
