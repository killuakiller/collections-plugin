jQuery(document).ready(function($) {
    function triggerAjaxFilter(paged = 1) {
        var container = $('.collection-layout');
        var collectionId = container.data('collection-id');
        var orderby = $('#collection-orderby').val() || '';
        
        // Gom tất cả các tag đang chọn ở sidebar
        var tags = [];
        $('.collection-sidebar input[type="checkbox"]:checked').each(function() {
            tags.push($(this).val());
        });

        var ajaxData = {
            action: 'collection_filter_collection',
            nonce: collectionsData.nonce,
            collection_id: collectionId,
            paged: paged,
            orderby: orderby
        };

        if (tags.length > 0) {
            ajaxData.tag = tags.join(',');
        }

        $.ajax({
            url: collectionsData.ajax_url,
            type: 'POST',
            data: ajaxData,
            beforeSend: function() {
                container.css('opacity', '0.5');
            },
            success: function(response) {
                container.css('opacity', '1');
                if (response.success) {
                    $('.collection-grid-container').html(response.data.grid);
                    $('.collection-pagination-container').html(response.data.pagination);
                    if (response.data.toolbar) {
                        $('.collection-toolbar-container').html(response.data.toolbar);
                    }
                    
                    // Cập nhật lại URL trình duyệt
                    var newUrl = window.location.pathname;
                    var params = new URLSearchParams();
                    if (tags.length > 0) params.set('tag', tags.join(','));
                    if (orderby) params.set('orderby', orderby);
                    if (paged > 1) params.set('paged', paged);
                    
                    if (params.toString()) {
                        newUrl += '?' + params.toString();
                    }
                    window.history.pushState({path: newUrl}, '', newUrl);
                }
            }
        });
    }

    // Bắt sự kiện chọn checkbox lọc
    $(document).on('change', '.collection-sidebar input[type="checkbox"]', function() {
        triggerAjaxFilter(1); // Khi đổi filter thì reset về trang 1
    });

    // Bắt sự kiện thay đổi sắp xếp (orderby)
    $(document).on('change', '#collection-orderby', function(e) {
        e.preventDefault();
        triggerAjaxFilter(1); // Khi đổi sort thì reset về trang 1
    });

    // Bắt sự kiện click phân trang (Pagination) cực kỳ an toàn
    $(document).on('click', '.collection-pagination-container a', function(e) {
        e.preventDefault();
        var href = $(this).attr('href');
        var paged = 1;

        // Bắt số trang từ dạng ?paged=2 hoặc &paged=2
        var matchQuery = href.match(/[?&]paged=(\d+)/);
        // Bắt số trang từ dạng /page/2/
        var matchPath = href.match(/\/page\/(\d+)\/?/);

        if (matchQuery) {
            paged = parseInt(matchQuery[1], 10);
        } else if (matchPath) {
            paged = parseInt(matchPath[1], 10);
        }

        triggerAjaxFilter(paged);
        $('html, body').animate({scrollTop: $('.collection-layout').offset().top - 100}, 300);
    });

    // Bắt sự kiện click nút "Show More / Show Less" trên mobile
    $(document).on('click', '.collection-filter-more-toggle', function() {
        var $group = $(this).closest('.collection-filter-group');
        var $wrapper = $group.find('.collection-filter-list-wrapper');
        var $moreText = $(this).find('.collection-toggle-text-more');
        var $lessText = $(this).find('.collection-toggle-text-less');

        // Đo chiều cao thật của nội dung thay vì dùng con số cố định (2000px)
        // để transition max-height chạy mượt, không bị khựng ở cuối animation.
        var fullHeight = $wrapper[0].scrollHeight;

        if ($group.hasClass('is-expanded')) {
            // Đang mở -> thu gọn lại
            // Set max-height về đúng giá trị hiện tại trước, để trình duyệt có điểm khởi đầu
            // chính xác cho transition (tránh nhảy từ "auto"/giá trị lớn).
            $wrapper.css('max-height', fullHeight + 'px');
            // Force reflow để trình duyệt ghi nhận giá trị vừa set trước khi đổi tiếp
            $wrapper[0].offsetHeight;

            $group.removeClass('is-expanded').addClass('is-collapsed');
            $wrapper.css('max-height', '38px');
            $moreText.show();
            $lessText.hide();
        } else {
            // Đang thu gọn -> mở ra đúng bằng chiều cao thật của nội dung
            $group.removeClass('is-collapsed').addClass('is-expanded');
            $wrapper.css('max-height', fullHeight + 'px');
            $moreText.hide();
            $lessText.show();

            // Sau khi transition xong, bỏ giới hạn cứng để nếu nội dung
            // thay đổi (ví dụ AJAX filter cập nhật số lượng) không bị cắt lại.
            setTimeout(function() {
                if ($group.hasClass('is-expanded')) {
                    $wrapper.css('max-height', 'none');
                }
            }, 400);
        }
    });
	// =========================================================================
    // CODE MỚI BỔ SUNG: TỰ ĐỘNG GIỚI HẠN 8 PILL & TẠO NÚT SHOW MORE/LESS
    // =========================================================================
    $('.collection-hub-grid').each(function() {
        var $grid = $(this);
        var $cards = $grid.find('.collection-hub-card');
        var total = $cards.length;
        var maxVisible = 8; // Chỉ hiện tối đa 8 pill

        if (total > maxVisible) {
            var hiddenCount = total - maxVisible;
            
            var $toggleBtn = $(
                '<button type="button" class="collection-hub-toggle-btn">' +
                    '<span class="toggle-text-more">Show More (' + hiddenCount + ') +</span>' +
                    '<span class="toggle-text-less" style="display:none;">Show Less -</span>' +
                '</button>'
            );

            $grid.after($toggleBtn);

            $toggleBtn.on('click', function() {
                var $moreText = $(this).find('.toggle-text-more');
                var $lessText = $(this).find('.toggle-text-less');

                if ($grid.hasClass('is-expanded')) {
                    $grid.removeClass('is-expanded');
                    $moreText.show();
                    $lessText.hide();
                } else {
                    $grid.addClass('is-expanded');
                    $moreText.hide();
                    $lessText.show();
                }
            });
        }
    });
});