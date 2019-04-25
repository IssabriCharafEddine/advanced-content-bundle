# Sherlockode AdvancedContentBundle

This bundle provides advanced CMS features inspired by the Wordpress ACF plugin.

Users are able to define content types as a list of fields.
These models are used by back-office contributors to create actual contents based on the previously defined structure.

[![Total Downloads](https://poser.pugx.org/sherlockode/advanced-content-bundle/downloads)](https://packagist.org/packages/sherlockode/advanced-content-bundle)
[![Latest Stable Version](https://poser.pugx.org/sherlockode/advanced-content-bundle/v/stable)](https://packagist.org/packages/sherlockode/advanced-content-bundle)

Installation
------------

### Get the bundle using composer

The best way to install this bundle is to rely on [Composer](https://getcomposer.org/):

```bash
$ composer require sherlockode/advanced-content-bundle
```

### Enable the bundle

Register the bundle in your application's kernel:

```php
// app/AppKernel.php
class AppKernel extends Kernel
{
    public function registerBundles()
    {
        $bundles = [
            // ...
            new Sherlockode\AdvancedContentBundle\SherlockodeAdvancedContentBundle(),
            // ...
        ];
    }
}
```

Configuration
-------------

### Entities

`SherlockodeAdvancedContentBundle` provides 6 entity models : ContentType, Layout, Field, Content, FieldGroupValue and FieldValue
To be able to use them, you need to create your own entities, see examples in the [doc](Resources/doc/entities.md).

Next, make the bundle aware of your entities by adding the following lines to your configuration:

```yaml
# app/config/config.yml
doctrine:
    orm:
        resolve_target_entities:
            SherlockodeAdvancedContentBundle\Model\ContentTypeInterface: AppBundle\Entity\ContentType
            SherlockodeAdvancedContentBundle\Model\ContentInterface: AppBundle\Entity\Content
            SherlockodeAdvancedContentBundle\Model\FieldInterface: AppBundle\Entity\Field
            SherlockodeAdvancedContentBundle\Model\FieldValueInterface: AppBundle\Entity\FieldValue
            SherlockodeAdvancedContentBundle\Model\FieldGroupValueInterface: AppBundle\Entity\FieldGroupValue
            SherlockodeAdvancedContentBundle\Model\LayoutInterface: AppBundle\Entity\Layout

sherlockode_advanced_content:
    entity_class:
        content_type: AppBundle\Entity\ContentType
        field: AppBundle\Entity\Field
        content: AppBundle\Entity\Content
        field_value: AppBundle\Entity\FieldValue
        field_group_value: AppBundle\Entity\FieldGroupValue
        layout: AppBundle\Entity\Layout
```


### Upload configuration

If you want to use the `Image` field type, you need to configure the directory in which the images will be saved.

If not defined, all images will be saved in the system's temporary directory.

The `uri_prefix` is used to retrieve the image on display.
The resulting image URL will be the URI prefix with the uploaded file name appended.

```yaml
# app/config/config.yml
sherlockode_advanced_content:
    upload:
        image_directory: '%kernel.project_dir%/uploads/acb_images'
        uri_prefix: /uploads/acb_images
```


### Routing

```yaml
# app/config/routing.yml
sherlockode_advanced_content:
    resource: '@SherlockodeAdvancedContentBundle/Resources/config/routing.xml'
```


Usage
-----

The bundle provides a twig function that will render the html of a field for a given content : 

```twig
{{ acb_field(content, slug) }}
```

Note that each FieldType has a `render()` method that will output the html for a given field.


For fields with hierarchy, like the Repeater, you will need to build a loop that will browse each
group of FieldValue and allow you to render each field of this group.
```twig
{% for group in acb_groups(yourContent, 'my-repeater-field') %}
    {{ acb_group_field(group, 'first-field') }}
    {{ acb_group_field(group, 'other-field') }}
{% endfor %}
```

## License

This bundle is under the MIT license. Check the details in the [dedicated file](LICENSE)
