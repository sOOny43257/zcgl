<x-print-layout
    :template="$template"
    :order-no="$orderNo ?? ''"
    :meta-values="$metaValues ?? []"
    :rows="$rows ?? []"
    :show-index="data_get($template->config, 'table.show_index', true)"
    :show-total="$showTotal ?? data_get($template->config, 'table.show_total', false)"
    :total-label="$totalLabel ?? '合计'"
    :total-fields="$totalFields ?? []"
    :signatures="data_get($template->config, 'signatures', [])"
    :footer-text="data_get($template->config, 'footer.text', '')"
    :show-date="data_get($template->config, 'footer.show_date', true)"
/>
