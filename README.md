SolrBundle
==========
[![Build Status](https://secure.travis-ci.org/floriansemm/SolrBundle.png?branch=master)](http://travis-ci.org/floriansemm/SolrBundle) 
[![Latest Stable Version](https://poser.pugx.org/floriansemm/solr-bundle/v/stable.svg)](https://packagist.org/packages/floriansemm/solr-bundle)
[![Total Downloads](https://poser.pugx.org/floriansemm/solr-bundle/downloads.svg)](https://packagist.org/packages/floriansemm/solr-bundle)

Introduction
------------

This Bundle provides a simple API to index and query a Solr Index. 

## Installation

Installation is a quick (I promise!) 3 step process:

1. Download SolrBundle
2. Enable the Bundle
3. Configure the SolrBundle

### Step 1: Download SolrBundle

This bundle is available on Packagist. You can install it using Composer:

```bash
$ composer require floriansemm/solr-bundle
```

### Step 2: Enable the bundle

Finally, enable the bundle in the kernel

``` php
<?php
// app/AppKernel.php

public function registerBundles()
{
    $bundles = array(
        // ...
        new FS\SolrBundle\FSSolrBundle(),
    );
}
```

### Step 3: Configure the SolrBundle

``` yaml
# app/config/config.yml
fs_solr:
    endpoints:
        core1:
            host: host
            port: 8983
            path: /solr/core1
            core: corename
            timeout: 5
        core2:
            host: host
            port: 8983
            path: /solr/core2
            core: corename
            timeout: 5
```

With this config you can setup two cores: `core1` and `core2`. See section `Specify cores` for more information.

## Usage

### Annotations

To put an entity to the index, you must add some annotations to your entity:

```php
// your Entity

// ....
use FS\SolrBundle\Doctrine\Annotation as Solr;
    
/**
* @Solr\Document(repository="Full\Qualified\Class\Name")
* @ORM\Table()
*/
class Post
{
    /**
     * @Solr\Id
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */

    private $id;
    /**
     *
     * @Solr\Field(type="string")
     *
     * @ORM\Column(name="title", type="string", length=255)
     */
    private $title = '';

    /**
     * 
     * @Solr\Field(type="string")
     *
     * @ORM\Column(name="text", type="text")
     */
    private $text = '';

   /**
    * @Solr\Field(type="date")
    *
    * @ORM\Column(name="created_at", type="datetime")
    */
    private $created_at = null;
}
```

### Supported field types

Currently is a basic set of types implemented.

- string
- text
- date
- integer
- float
- double
- long
- boolean

It is possible to use custom field types (schema.xml).

### Filter annotation

In some cases a entity should not be index. For this you have the `SynchronizationFilter` Annotation.

```php
/**
 * @Solr\Document
 * @Solr\SynchronizationFilter(callback="shouldBeIndex")
 */
class SomeEntity
{
    /**
     * @return boolean
    */
    public function shouldBeIndex()
    {
        // put your logic here
    }
}
```

The callback property specifies an callable function, which decides whether the should index or not.    

### Specify cores

It is possible to specify a core dedicated to a document

```php
/**
 * @Solr\Document(index="core0")
 */
class SomeEntity
{
    // ...
}
```

All documents will be indexed in the core `core0`. If your entities/document have different languages then you can setup
a callback method, which returns the preferred core for the entity.

```php
/**
 * @Solr\Document(indexHandler="indexHandler")
 */
class SomeEntity
{
    public function indexHandler()
    {
        if ($this->language == 'en') {
            return 'core0';
        }
    }
}
```

Each core must setup up in the config.yml under `endpoints`. If you leave the `index` or `indexHandler` property empty,
then a default core will be used (first in the `endpoints` list). To index a document in all cores use `*` as index value:

```php
@Solr\Document(index="*")
```

### Solr field configuration

Solr comes with a set of predefined field-name/field-types mapping:

- title (solr-type: general_text)
- text (solr-type: general_text)
- category (solr-type: general_text)
- content_type (solr-type: string)

For details have a look into your schema.xml.

So if you have an entity with a property "category", then you don't need a type-declaration in the annotation:

```php
/**
 * @Solr\Field
 * @ORM\Column(name="category", type="text")
 */
private $category = '';
```

The field has in this case automaticaly the type "general_text".

If you persist this entity, it will put automatically to the index. Update and delete happens automatically too.

### Query a field of a document

To query the index you have to call some services.

```php
$query = $this->get('solr.client')->createQuery('AcmeDemoBundle:Post');
$query->addSearchTerm('title', 'my title');

$result = $query->getResult();
```

The $result array contains all found entities. The solr-service does all mappings from SolrDocument
to your entity for you.

### Query all fields of a document

The pervious examples have queried only the field 'title'. You can also query all fields with a string.

```php
$query = $this->get('solr.client')->createQuery('AcmeDemoBundle:Post');
$query->queryAllFields('my title');

$result = $query->getResult();
```

### Define Result-Mapping

To narrow the mapping, you can use the `addField()` method.

```php
$query = $this->get('solr.client')->createQuery('AcmeDemoBundle:Post');
$query->addSearchTerm('title', 'my title');
$query->addField('id');
$query->addField('text');

$result = $query->getResult();
```

In this case only the fields id and text will be mapped (addField()), so title and created_at will be
empty. If nothing was found $result is empty.

The result contains by default 10 rows. You can increase this value:

```php
$query->setRows(1000000);
```

### Configure HydrationModes

HydrationMode tells the Bundle how to create an entity from a document.

1. `FS\SolrBundle\Doctrine\Hydration\HydrationModes::HYDRATE_INDEX` - use only the data from solr
2. `FS\SolrBundle\Doctrine\Hydration\HydrationModes::HYDRATE_DOCTRINE` - merge the data from solr with the entire doctrine-entity

With a custom query:

```php
$query = $this->get('solr.client')->createQuery('AcmeDemoBundle:Post');
$query->setHydrationMode($mode)
```

With a custom document-repository you have to set the property `$hydrationMode` itself:

```php
public function find($id)
{
    $this->hydrationMode = HydrationModes::HYDRATE_INDEX;
    
    return parent::find($id);
}
```

### Index manually an entity

To index your entities manually, you can do it the following way:

```php
$this->get('solr.client')->addDocument($entity);
$this->get('solr.client')->updateDocument($entity);
$this->get('solr.client')->removeDocument($entity);
```

`removeDocument()` requires that the entity-id is set.

### Use document repositories

If you specify your own repository you must extend the `FS\SolrBundle\Repository\Repository` class. The usage is the same
like Doctrine-Repositories:

```php
$myRepository = $this->get('solr.client')->getRepository('AcmeDemoBundle:Post');
$result = $myRepository->mySpecialFindMethod();
``` 

If you haven't declared a concrete repository in your entity and you calling `$this->get('solr.client')->getRepository('AcmeDemoBundle:Post')`, you will
get an instance of `FS\SolrBundle\Repository\Repository`.

### Commands

There are two commands with this bundle:

* `solr:index:clear` - delete all documents in the index
* `solr:synchronize` - synchronize the db with the index
