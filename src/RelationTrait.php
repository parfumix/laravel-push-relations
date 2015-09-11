<?php

namespace Laravel\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

trait RelationTrait {


    /**
     * Refresh all relationShips .
     *
     * @param array $attributes
     * @return $this
     */
    public function refresh(array $attributes) {
        if(! $this['exists'])
            return $this;

        $toInsert = [];

        if( isset($this->relationShips) ) {
            foreach ($this->relationShips as $relation => $class) {
                if(array_key_exists($relation, $attributes))
                    $toInsert[$relation] = array_pull($attributes, $relation);
            }
        }

        foreach ($toInsert as $key => $value) {
            if( ! method_exists($this, $key) )
                continue;

            $relation = $this->{$key}();

            if( $relation instanceof HasOne ) {

                $relation->updateOrCreate(isset($value['id']) ? ['id' => $value['id']] : [], array_except($value, ['id']));
            } elseif( $relation instanceof HasMany ) {

                foreach ($value as $v) {
                    $relation = $this->{$key}();
                    $relation->updateOrCreate(isset($v['id']) ? ['id' => $v['id']] : [], array_except($v, ['id']));
                }

            } elseif( $relation instanceof BelongsTo ) {
                $value = ! is_array($value) ? [$value] : $value;

                foreach ($value as $k => $v)
                    if( $row = $relation->getRelated()->find($value) )
                        $relation->associate($row);

            } elseif( $relation instanceof BelongsToMany ) {

                foreach ($value as $v) {
                    if(! is_array($v)) {
                        if( $row = $relation->getRelated()->find($v) ) {
                            $relation->sync(
                                [$row->getKey()]
                            );
                        }
                    } else {
                        if( $row = $relation->getRelated()->updateOrCreate( ['id' => isset($v['id']) ? $v['id'] : null], array_except($v, ['pivot']) ) ) {
                            $row->fill(array_except($v, ['id', 'pivot']));

                            if( isset($v['sync']) )
                                $relation->sync([$row->getKey() => array_only($v, 'pivot')]);
                            else
                                $relation->attach($row, array_only($v, 'pivot'));
                        }
                    }
                }
            } elseif( $relation instanceof MorphMany ) {
                #@todo save morph many relationShipships
            } elseif( $relation instanceof MorphToMany ) {
                #@todo save many to many morph relationShipships .
            }
        }

        return $this;
    }
}