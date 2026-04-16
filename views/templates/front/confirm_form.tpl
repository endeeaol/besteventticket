{extends file='page.tpl'}

{block name='page_content'}
<div class="container">
  <div class="card" style="max-width:900px;margin:40px auto;padding:24px;">
    <h1 style="margin-bottom:16px;">Potwierdzenie uczestników wydarzenia</h1>

    {if $bet_error}
      <div class="alert alert-danger">{$bet_error|escape:'html':'UTF-8'}</div>
    {else}
      <p>
        Kupiłeś(-aś) <strong>{$bet_qty|intval}</strong>
        {if $bet_qty == 1}bilet{elseif $bet_qty < 5}bilety{else}biletów{/if}
        na wydarzenie:
        <strong>{$bet_event_name|escape:'html':'UTF-8'}</strong>
      </p>

      <p>
        Numer zamówienia:
        <strong>{$bet_id_order|intval}</strong>
      </p>

      <form action="{$bet_action|escape:'html':'UTF-8'}" method="post">
        <input type="hidden" name="token" value="{$bet_token|escape:'html':'UTF-8'}">
        <input type="hidden" name="submitBestEventTicketConfirmation" value="1">

        {if $bet_qty == 1}
          <p>
            Dane uczestnika zostaną potwierdzone automatycznie na podstawie danych zamówienia.
          </p>

          <div class="form-group" style="margin-bottom:20px;">
            <label>Uczestnik</label>
            <input
              type="text"
              class="form-control"
              value="{$bet_default_single_guest_name|escape:'html':'UTF-8'}"
              disabled="disabled"
            >
          </div>

          <button type="submit" class="btn btn-primary">
            Potwierdzam
          </button>
        {else}
          <p>
            Prosimy o podanie danych gości. Pozostawienie pustego pola będzie oznaczało rezygnację z miejsca.
          </p>

          {foreach from=$bet_tickets item=ticket}
            <div class="form-group" style="margin-bottom:16px;">
              <label for="guest_name_{$ticket.ticket_position|intval}">
                Bilet {$ticket.ticket_position|intval}
              </label>
              <input
                id="guest_name_{$ticket.ticket_position|intval}"
                type="text"
                name="guest_name_{$ticket.ticket_position|intval}"
                class="form-control"
                value="{$ticket.guest_name|escape:'html':'UTF-8'}"
                placeholder="Imię i nazwisko gościa"
              >
            </div>
          {/foreach}

          <button type="submit" class="btn btn-primary">
            Potwierdzam liczbę gości według podanych danych
          </button>
        {/if}
      </form>
    {/if}
  </div>
</div>
{/block}