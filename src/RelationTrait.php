<?php

namespace Laravel\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

trait RelationTrait {

    public $related = [];

    protected $relation = [
        'phone',
        'comments',
        'roles',
    ];

    /**
     * Refresh all relations .
     *
     * @param array $attributes
     * @return $this
     */
    public function refresh(array $attributes) {
        if( isset($this->relation) ) {
            foreach ($this->relation as $relation) {
                if(array_key_exists($relation, $attributes))
                    $this->related[$relation] = array_pull($attributes, $relation);
            }
        }

        foreach ($this->related as $key => $value) {
            if( ! method_exists($this, $key) )
                continue;

            $relation = $this->{$key}();


            if( $relation instanceof HasOne ) {

                $relation->updateOrCreate(isset($value['id']) ? ['id' => $value['id']] : [], array_except($value, ['id']));
            } elseif( $relation instanceof HasMany ) {

                foreach ($value as $v)
                    $relation->updateOrCreate(isset($v['id']) ? ['id' => $v['id']] : [], array_except($v, ['id']));

            } elseif( $relation instanceof BelongsTo ) {

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
            }
        }

        return $this;
    }
}