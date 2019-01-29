'use strict';

/**
 * Clone product extension
 */
define([
    'pim/form',
    'underscore',
    'oro/translator',
    'backbone',
    'pim/form-builder',
    'flagbit/template/product-model/clone-button',
    'flagbit/template/product-model/clone-modal',
    ],
    function (
        BaseForm,
        _,
        __,
        Backbone,
        FormBuilder,
        template,
        templateModal
    ) {
        return BaseForm.extend({
            template: _.template(template),
            templateModal: _.template(templateModal),

            events: {
                'click .clone-product-model-button': 'openModal'
            },

            initialize(config) {
                this.config = config.config;

                BaseForm.prototype.initialize.apply(this, arguments);
            },

            openModal() {
                return FormBuilder.build(this.config.formName).then(modal => {
                    modal.setData('code_to_clone', this.getRoot().model.get('code'));
                    modal.open();
                });
            },

            /**
             * {@inheritdoc}
             */
            getIdentifier: function () {
                return this.getFormData().meta.id;
            },

            /**
             * {@inheritdoc}
             */
            render: function () {
                if (!this.getFormData().meta) {
                    return;
                }

                this.$el.html(this.template());

                return this;
            }
        });
});
