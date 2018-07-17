jQuery(function ($) {
    'use strict';

    updateChoiceList();
    hideEmptyOptionsRow();

    function ajaxFailCallback(jqXhr) {
        alert('An error occurred.');
    }

    $('.form-create-field').on('submit', function (e) {
        e.preventDefault();
        var button = $('.btn-add-field');
        button.prop('disabled', true);
        $.ajax({
            url: $(this).attr('action'),
            type: 'POST',
            data: $(this).serialize()
        }).done(function (resp) {
            if (resp.success) {
                $('.acb-fields').append(resp.html);
                $('.acb-fields .panel:last-of-type .edit-field').click();
                hideEmptyOptionsRow();
            } else {
                $('.add-field-form').html(resp.html);
            }
        }).always(function () {
            button.prop('disabled', false);
        }).fail(ajaxFailCallback);
    });

    $('.acb-fields').on('click', '.remove-field', function (e) {
        var fieldRow = $(this).closest('.field-row');
        fieldRow.remove();
    });

    $('.acb-fields').on('change', '.field-type', function (e) {
        var fieldRow = $(this).closest('.field-row');
        var fieldId = fieldRow.data('field-id');
        var contentTypeId = fieldRow.data('content-type-id');
        var data = {
            contentTypeId: contentTypeId,
            fieldId: fieldId,
            type: $(this).val()
        };
        $.ajax({
            url: $('.edit-content-type').data('change-type-url'),
            type: 'POST',
            data: data,
            context: this
        }).done(function (data) {
            $(this).closest('div.field-row').find('.options').html(data.html);
            updateChoiceList();
            hideEmptyOptionsRow();
        }).fail(ajaxFailCallback);
    });

    function updateChoiceList() {
        $('.choice-list').find('li').each(function() {
            if ($(this).find('.delete-choice').length === 0) {
                addChoiceRemoveLink($(this));
            }
        });
    }
    $('body').on('click', '.add-another-choice', function (e) {
        e.preventDefault();
        var list = $(this).siblings('.choice-list');
        var counter = list.data('widget-counter');
        var newWidget = list.data('prototype');

        counter++;
        newWidget = newWidget.replace(/__name__/g, counter);
        list.data('widget-counter', counter);

        var newElem = $(list.data('widget-tags')).html(newWidget);
        addChoiceRemoveLink(newElem);
        newElem.appendTo(list);
    });
    $('body').on('click', '.delete-choice', function (e) {
        e.preventDefault();
        $(this).closest('.choice-row').remove();
    });
    function addChoiceRemoveLink(choiceLi) {
        choiceLi.append($('.field-options-remove-link').html());
    }

    function hideEmptyOptionsRow() {
        $('.options-row').each(function() {
            $(this).show();
            if ($(this).find('.no-option').length > 0) {
                $(this).hide();
            }
        });
    }
});
