# magento-vigilant-form-kit
Magento Module for VigilantForm.

## So what is this?
A Magento Module to make it easy to push form submissions into an instance of VigilantForm.

## So how is it used?
First you add the library:
```bash
composer require digitaladapt/magento-vigilant-form-kit
```

Then setup a config file `vigilantform.json` in the root of your Magento installation:
website and form_title will default to hostname and "submit" respectively.
```json
{
  "url":          "<SERVER_URL>",
  "clientId":     "<CLIENT_ID>",
  "secret":       "<CLIENT_SECRET>",
  "prefix":       null,
  "honeypot":     null,
  "sequence":     null,
  "script_src":   null,
  "script_class": null,
  "website":      null,
  "form_title":   null
}
```

Then use dependency injection to get the `\VigilantForm\MagentoKit\VigilantFormMagentoKit` class into whatever block or controller which has the form you want to validate.
```php
// SomeBlock.php
<?php

namespace SomeVendor\SomeModule\Block;

class SomeBlock extends \Magento\Framework\View\Element\Template
{
    protected $vfmk;

    public function __construct(\VigilantForm\MagentoKit\VigilantFormMagentoKit $vfmk)
    {
        $this->vfmk = $vfmk;
    }

    public function getVFMK()
    {
        return $this->vfmk;
    }
}
```

Within the form template you call generateHoneypot() within the html form:
```php
// some_block.phtml
<?php /** @var \SomeVendor\SomeModule\Block\SomeBlock $block */ ?>
<form>
<?php echo $block->getVFMK()->generateHoneypot(); ?>
</form>
```

When handling form submissions, you also dependency inject the VigilantFormMagentoKit 
class, which has the `submitForm()` function. If the submission fails to be stored,
it will throw an UnexpectedValueException.
```php
    $params = $this->getRequest()->getPost();
    try {
        $this->vfmk->submitForm($params);
    } catch (\UnexpectedValueException $exception) {
        /* do something, in the event failed to store form submission */
    }

```

Finally, redeploy your Magento website to detect the new module and recompile the dependency injection.
