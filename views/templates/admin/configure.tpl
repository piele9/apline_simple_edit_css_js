{*
 * APLINE Simple Edit CSS/JS module for PrestaShop 9.
 * @author APLINE Arkadiusz Pielechowski
 *}
<div class="panel">
  <h3><i class="icon-code"></i> {l s='Simple Edit CSS/JS' d='Modules.Aplinesimpleeditcssjs.Admin'}</h3>
  <p>{l s='Inject custom CSS and JavaScript snippets into the front-end without editing your theme. Each snippet is rendered inline on every front-end page.' d='Modules.Aplinesimpleeditcssjs.Admin'}</p>
  <p class="alert alert-warning">
    <i class="icon-warning"></i>
    {l s='Snippets can break your storefront if the code contains errors. Always test on a staging copy before enabling a snippet in production.' d='Modules.Aplinesimpleeditcssjs.Admin'}
  </p>
  <a href="{$asec_manage_url|escape:'html':'UTF-8'}" class="btn btn-primary">
    <i class="icon-list"></i> {l s='Manage snippets' d='Modules.Aplinesimpleeditcssjs.Admin'}
  </a>
</div>
