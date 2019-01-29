'use strict';

/**
 * Clone product extension
 */
define([
    'jquery',
    'pim/form',
    'underscore',
    'oro/translator',
    'backbone',
    'pim/form-builder',
    'flagbit/template/product/clone-button',
    'pim/form-modal',
    ],
    function (
        $,
        BaseForm,
        _,
        __,
        Backbone,
        FormBuilder,
        template,
        FormModal
    ) {
        return BaseForm.extend({
            template: _.template(template),

            initialize(config) {
                this.config = config.config;
                BaseForm.prototype.initialize.apply(this, arguments);
            },

            openModal() {
                return FormBuilder.build(this.config.formName).then(modal => {

                    const initialModalState = {
                        parent: this.getRoot().model.get('parent'),
                        values: {},
                        code_to_clone: this.getRoot().model.get('identifier')
                    };
                    modal.setData(initialModalState);
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

                $('.clone-product-button').on('click', () => {
                    this.openModal();
                });
                return this;
            }
        });
});
