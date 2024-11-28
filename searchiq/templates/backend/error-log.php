<?php
$page = isset($_GET['err_log_page']) ? intval(sanitize_text_field($_GET['err_log_page'])) : 0;
$limit = isset($_GET['records_per_page']) ? intval(sanitize_text_field($_GET['records_per_page'])) : 10;
$recordsCount = $this->getAPIErrorRecordsCount();
$records = $this->getAPIErrorRecords($page, $limit);
$max_page = max(0, ceil($recordsCount / $limit) - 1);
$nonceError = false;

// if delete request
if (isset($_GET['delete']) && sanitize_text_field($_GET['delete']) > 0) {
    if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'delete_single_errorlog')) {
        $delete_record_id = intval(sanitize_text_field($_GET['delete']));
        $this->deleteAPIErrorLogRecord($delete_record_id);
        $redirect = '?page=dwsearch&tab=tab-7&err_log_page=' . min($page, $max_page) . '&records_per_page=' . $limit;
        ?>
    <script type="text/javascript">location.href = '<?php echo $redirect; ?>';</script><?php
         exit;
    }else {
        $nonceError = true;
    }
}

if (isset($_GET['delete_all']) && sanitize_text_field($_GET['delete_all']) == 1) {
    if (wp_verify_nonce(sanitize_text_field($_GET['_wpnonce']), 'delete_all_errorlog')) {
        $this->deleteAllAPIErrorLogRecords();
        $redirect = '?page=dwsearch&tab=tab-7&err_log_page=0&records_per_page=' . $limit;
        ?>
        <script type="text/javascript">location.href = '<?php echo $redirect; ?>';</script><?php
           exit;
    } else {
        $nonceError = true;
    }
}

if ($page > $max_page || $page < 0) {
    $redirect = '?page=dwsearch&tab=tab-7&err_log_page=' . $max_page . '&records_per_page=' . $limit;
    ?>
    <script type="text/javascript">location.href = '<?php echo $redirect; ?>';</script>
    <?php
    exit;
}

?>
<div class="wsplugin">
    <?php if ($nonceError): ?>
        <div class="notice notice-error inline searchiq-deleteall-nonce-error">Invalid nonce. Please reload the page and try
            again.</div>
    <?php endif; ?>
    <h2>Error Log</h2>
    <div class="section section-1">
        <?php
        if ($recordsCount > 0) {
            ?><a
                href="<?php echo add_query_arg('_wpnonce', wp_create_nonce('delete_all_errorlog'), '?page=dwsearch&tab=tab-7&delete_all=1') ?>"
                onclick="return confirm('Are you sure you want to delete all error log records?');">Delete All Records</a><?php
        }
        ?>
        <div style="overflow-x: auto;">
            <table width="100%" cellspacing="0" cellpadding="0" border="0" id="siq-error-log-tbl"
                class="tableCustomPostTypeImages">
                <thead>
                    <tr>
                        <th>Timestamp</th>
                        <th>Method</th>
                        <th>URL</th>
                        <th>Request Body</th>
                        <th>Status Code</th>
                        <th>Response</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($recordsCount === 0) {
                        ?>
                        <tr>
                            <td colspan="7" style="text-align:center;">
                                No records
                            </td>
                        </tr>
                        <?php
                    } else {
                        foreach ($records as $row) {
                            ?>
                            <tr>
                                <td style="white-space: nowrap;"><?php _e($row->timestamp); ?></td>
                                <td><?php _e($row->request_method); ?></td>
                                <td>
                                    <div class="siq-tbl-wrp-long-content"><?php _e($row->request_url); ?></div>
                                </td>
                                <td>
                                    <div class="siq-tbl-wrp-long-content"><?php _e($row->request_body); ?></div>
                                </td>
                                <td><?php _e($row->status_code); ?></td>
                                <td>
                                    <div class="siq-tbl-wrp-long-content"><?php _e($row->response); ?></div>
                                </td>
                                <td><a
                                        href="<?php esc_html_e(add_query_arg('_wpnonce', wp_create_nonce('delete_single_errorlog'),"?page=dwsearch&tab=tab-7&delete=" . $row->id . "&err_log_page=" . $page . "&records_per_page=" . $limit)); ?>">delete</a>
                                </td>
                            </tr>
                            <?php
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <div class="error-log-pagination-wrapper">
            <?php
            if ($recordsCount > $limit) {
                $link_tpl = "?page=dwsearch&tab=tab-7&records_per_page=" . $limit . "&err_log_page=";
                $pagination_first = 0;
                $pagination_last = ceil($recordsCount / $limit) - 1;
                $pagination_start = max(0, $page - 3);
                $pagination_end = min($pagination_last, $page + 3);
                if ($pagination_first < $pagination_start) {
                    ?><a class="error-log-pagination pagination-link active"
                        href="<?php esc_html_e($link_tpl . $pagination_first); ?>"><?php _e($pagination_first + 1); ?></a><?php
                }
                if ($pagination_first + 1 < $pagination_start) {
                    ?><span class="error-log-pagination">...</span><?php
                }
                for ($i = $pagination_start; $i <= $pagination_end; $i++) {
                    if ($page === $i) {
                        ?><span class="error-log-pagination pagination-link"><?php _e($i + 1); ?></span><?php
                    } else {
                        ?><a class="error-log-pagination pagination-link active"
                            href="<?php esc_html_e($link_tpl . $i); ?>"><?php _e($i + 1); ?></a><?php
                    }
                }
                if ($pagination_end + 1 < $pagination_last) {
                    ?><span class="error-log-pagination">...</span><?php
                }
                if ($pagination_end < $pagination_last) {
                    ?><a class="error-log-pagination pagination-link active"
                        href="<?php esc_html_e($link_tpl . $pagination_last); ?>"><?php _e($pagination_last + 1); ?></a><?php
                }
            }
            ?>
        </div>
    </div>
</div>