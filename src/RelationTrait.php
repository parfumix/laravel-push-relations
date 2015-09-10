<?php

namespace Laravel\Relations;

trait RelationTrait {

    public $related = [];

    /**
     * We have to extract all attributes which are the relations and store them ..
     *
     * Processing ...
     *
     *  1. check fo relations attribute, if there is relation that mean that fields is part of relation and we have to use
     *      create or createMany functions to store the relations
     *
     *    Walk through relations ....
     *
     *    a. as we have the array of relations need to be stored we have to check the type of relations so we will use there
     *
     *       if is one to one OR one to many than
     *
     *          We gonna walk through results and do that .
     *
     *         1. if it is parent relation than we have to to use updateOrCreate function to update the relations $user->comments()->updateOrCreate($id, $attributes)
     *
     *       if is many to many relation than
     *
     *         1.
     */
    public function fill(array $attributes) {
        if( isset($this->relations) ) {
            foreach ($this->relations as $relation) {
                if(in_array($relation, $attributes))
                    $this->related[$relation] = array_pull($relation, $attributes);
            }
        }

        return parent::fill($attributes);
    }
}