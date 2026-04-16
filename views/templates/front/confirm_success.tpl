{extends file='page.tpl'}

{block name='page_content'}
<div class="container">
  <div class="card" style="max-width:900px;margin:40px auto;padding:24px;">
    <h1 style="margin-bottom:16px;">Dziękujemy</h1>

    <p>
      Zapisaliśmy potwierdzenie dla wydarzenia:
      <strong>{$event_name|escape:'html':'UTF-8'}</strong>
    </p>

    <p>
      Numer zamówienia:
      <strong>{$id_order|intval}</strong>
    </p>

    <p>
      Potwierdzone miejsca:
      <strong>{$confirmed_count|intval}</strong> / <strong>{$total_count|intval}</strong>
    </p>

    <p>Do zobaczenia!</p>
  </div>
</div>
{/block}