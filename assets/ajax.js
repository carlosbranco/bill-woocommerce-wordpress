jQuery(document).ready(function($) {
    $(".procurar-produto").click(function() {
        var linha = jQuery(this).parent().parent().parent().parent();
        var data = {
            action: 'procurar_item',
            codigo: linha.find('.sku').val()
        };
        $.post(the_ajax_script.ajaxurl, data, function(response) {
            response = jQuery.parseJSON(response);
            if (response != null && response.id) {
                linha.find('.sku').val(response.codigo);
                var key = linha.find('.item_id').data('key');
                linha.find('.item_id').html('<span class="tag">' + response.id + '<input type="hidden" name="produtos[' + key + '][item_id]" value="' + response.id + '" /></span>');
                linha.next().find('td').html('<div class="tag"><strong>ID: </strong>' + response.id + '</div> <div class="tag"><strong> CODE: </strong>' + response.codigo + '</div> <div class="tag"><strong> DESC: </strong>' + response.descricao + '</div>');
            } else {
                linha.find('.item_id span').html('<span class="tag is-success">NEW</span>');
                alert('O código não foi encontrado. O produto será criado quando gerar o documento. Se quer associar este produto a um produto já existente no bill por favor coloque o código correcto e clique em procurar.');
            }
        });
        return false;
    });


    jQuery('.tgl-flat').not('#terminado').change(function() {
        if (jQuery(this).is(':checked')) {
            $('.tgl-flat').not('#terminado').attr("checked", true);
            jQuery('.moradas').fadeIn(300);
            jQuery('.moradas select, .moradas input, .moradas textarea').each(function() {
                jQuery(this).attr('name', jQuery(this).data("name")).attr("required", "required");
            });
            return;
        }

        $('.tgl-flat').not('#terminado').attr("checked", false);
        jQuery('.moradas').fadeOut(300);
        jQuery('.moradas select, .moradas input, .moradas textarea').removeAttr("name").removeAttr("required");
        return;
    });

    jQuery('#finalizar_documento').on('click', function(e) {
        e.preventDefault();
        jQuery('#terminado').val(1);
        jQuery('#formulario_criar_documento').submit();
    });

    jQuery('#criar_rascunho').on('click', function(e) {
        e.preventDefault();
        jQuery('#terminado').val(0);
        jQuery('#formulario_criar_documento').submit();
    });

});