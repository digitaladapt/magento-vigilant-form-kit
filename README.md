# magento-vigilant-form-kit
Magento Module for VigilantForm.

**Warning: Alpha release, still working out all the details.**

## So what is this?
I'm working on a Magento Module to make it easy to push form submissions into an instance of VigilantForm.

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

**TODO: Once I have finished implementing everything, I need to revisit documentation to ensure it is complete and accurate.**

Finally, redeploy your Magento website to detect the new module and recompile the dependency injection.

I will probably refactor the Bootstrap class to simplify the process of generating the honeypot and submitting the form, but for now:

Now you can add the honeypot field into any forms you want to validate.
Where you echo the `generateHoneypot()` from the VigilantFormKit into your form template.

When handling form submissions, you can dependency inject the Bootstrap 
class, which has a `create()` function, to tap into, so you can get the VigilantFormKit and call `submitForm()`.
