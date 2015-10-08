<?php

namespace Laravel\Relations;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;
use Flysap\Users;

trait RelationTrait {

    /**
     * Refresh all relation .
     *
     * @param array $attributes
     * @return $this
     */
    public function refresh(array $attributes) {
        if(! $this['exists'])
            return $this;

        $toInsert = [];

        if( isset($this->relation) ) {
            foreach ($this->relation as $relation => $options) {
                if( is_numeric($relation)) {
                    $relation = $options; $options = [];
                }

                if(! $this->isGranted($options))
                    continue;

                /** If security is granted than add relation to processing . */
                if(array_key_exists($relation, $attributes))
                    $toInsert[$relation] = array_pull($attributes, $relation);
            }
        }

        foreach ($toInsert as $key => $value) {
            if( ! method_exists($this, $key) )
                continue;

            $relation = $this->{$key}();

            if( $relation instanceof HasOne ) {

                if(isset($value[0]) && is_array($value[0]))
                    $value = $value[0];

                $relation->updateOrCreate(isset($value['id']) ? ['id' => $value['id']] : [], array_except($value, ['id']));
            } elseif( $relation instanceof HasMany ) {

                foreach ($value as $v) {
                    $relation = $this->{$key}();

                    $related = $relation->getRelated();

                    if(! $instance = $related->whereId(isset($v['id']) ? $v['id'] : null)->first()) {
                        $instance = $related->newInstance();
                        $instance->setAttribute($relation->getPlainForeignKey(), $relation->getParentKey());
                    }

                    $instance->fill(array_except($v, ['id']));
                    $instance->save();
                }

            } elseif( $relation instanceof BelongsTo ) {
                $value = ! is_array($value) ? [$value] : $value;

                foreach ($value as $k => $v) {
                    if( ! $v || empty($v))
                        continue;

                    if( $row = $relation->getRelated()->find($value)->first() )
                        $relation->associate($row);
                }

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
                #@todo save morph many relationhips
            } elseif( $relation instanceof MorphToMany ) {
                #@todo save many to many morph relationhips .
            }
        }

        return $this;
    }

    /**
     * Allow current user to update that relation ?
     *
     * @param array $attributes
     * @param array $defaultAllowedRole
     * @return bool
     */
    protected function isGranted(array $attributes, $defaultAllowedRole = ['admin']) {
        #@todo  delete it .
        return true;
        /** By default we have to add that only to admins . */
        if(! isset($attributes['permissions']) && !isset($attributes['roles']))
            $attributes['roles'] = $defaultAllowedRole;

        if( isset($attributes['permissions']) )
            if( ! Users\can($attributes['permissions']) )
                return false;

        if( isset($attributes['roles']) )
            if(! Users\is($attributes['roles']))
                return false;

        return true;
    }
}