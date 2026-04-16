<div class="panel">
    <div class="row" style="display:flex; gap:20px;">

        {foreach from=$bet_event_stats key=id_product item=stat}
            <div style="
                flex:1;
                background:#f8f9fa;
                padding:16px;
                border-radius:6px;
                border:1px solid #ddd;
            ">
                <div style="font-size:16px; font-weight:600;">
                    {$stat.name} [{$id_product}]
                </div>

                <div style="margin-top:8px; font-size:14px;">
                    potwierdzono
                    <strong>{$stat.confirmed}</strong>
                    z
                    <strong>{$stat.total}</strong>
                </div>
            </div>
        {/foreach}

    </div>
</div>