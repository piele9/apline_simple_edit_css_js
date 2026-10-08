{*
 * APLINE Simple Edit CSS/JS module for PrestaShop 9.
 * @author Arkadiusz Pielechowski
 *}
<div class="panel">
  <h3><i class="icon-code"></i> {l s='Fragmenty CSS/JS' d='Modules.Aplinesimpleeditcssjs.Admin'}</h3>
  <p>{l s='Wstawiaj własne fragmenty CSS i JavaScript na stronę sklepu bez edytowania motywu. Każdy aktywny fragment jest wstawiany bezpośrednio w kod każdej strony sklepu.' d='Modules.Aplinesimpleeditcssjs.Admin'}</p>
  <p class="alert alert-warning">
    <i class="icon-warning"></i>
    {l s='Fragment z błędem w kodzie może zepsuć wygląd lub działanie sklepu. Zawsze testuj go na kopii testowej, zanim włączysz go w działającym sklepie.' d='Modules.Aplinesimpleeditcssjs.Admin'}
  </p>
  <a href="{$asec_manage_url|escape:'html':'UTF-8'}" class="btn btn-primary btn-lg apline-btn-duzy">
    <i class="icon-list"></i> {l s='Zarządzaj fragmentami CSS/JS' d='Modules.Aplinesimpleeditcssjs.Admin'}
  </a>
</div>
