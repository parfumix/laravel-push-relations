##Introduction

Eloquent push relations is an way to quickly save your relations based on eloquent declared relations. Of course you have to follow an specific standart. 

### Instalation
You can use the `composer` package manager to install. From console run:

```
  $ php composer.phar require parfumix/laravel-push-relations "v1.0"
```

or add to your composer.json file

    "parfumix/laravel-push-relations": "v1.0"


##Basic usage

Before you start working you have to include the main trait ***RelationTrait*** which will give the functionality. Below are showed an example.

```php
<?php

namespace App;

use Laravel\Relations\RelationTrait;
use Illuminate\Database\Eloquent\Model;

class Page extends Model {

    use RelationTrait;
    
    public $relations = [
       'comments' => $this->comments()
    ];
    
    public function comments() {
        return $this->hasMany(Comments::class);
    }
}

class Comment extends Model {
    
    public function page() {
        return $this->belongsTo(Page::class)
    }
}
```

To store the relations you have to 

```php
 
 if($_POST) {
     $page = App\Page::find($request->get('page_id'))
     $page->fill($request->all())
         ->refresh($request->all())
         ->save();
 }
 
```
But before to send your post you have to know which format you have to follow .

###Formats

***1:1*** relation


