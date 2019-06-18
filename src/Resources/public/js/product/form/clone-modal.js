'use strict';

define(
    [
        'jquery',
        'underscore',
        'oro/translator',
        'backbone',
        'routing',
        'pim/form',
        'pim/form-builder',
        'pim/user-context',
        'oro/loading-mask',
        'pim/router',
        'oro/messenger',
        'flagbit/template/product/clone-modal'
    ],
    function (
        $,
        _,
        __,
        Backbone,
        Routing,
        BaseForm,
        FormBuilder,
        UserContext,
        LoadingMask,
        router,
        messenger,
        template
    ) {
        return BaseForm.extend({
            config: {},
            template: _.template(template),
            globalErrors: [],
            /**
             * {@inheritdoc}
             */
            initialize(meta) {
                this.config = meta.config;
                this.globalErrors = [];
                BaseForm.prototype.initialize.apply(this, arguments);
            },
            getFieldsFormName() {
                if (this.getRoot().model.has('parent')) {
                    return this.config.variantFormName;
                }
                else {
                    return this.config.productFormName;
                }
            },

            render() {


                this.$el.html(this.template({
                    modalTitle: __(this.config.labels.title),
                    subTitle: __(this.getSubtitle()),
                    content: __(this.config.labels.content),
                    picture: this.config.picture,
                    errors: this.globalErrors
                }));

                return FormBuilder.build(this.getFieldsFormName()).then(form => {
                    this.addExtension(
                        form.code,
                        form,
                        'fields-container',
                        10000
                    );
                    form.configure();
                    this.renderExtensions();
                    return this;
                });
            },

            getIllustrationClass() {
                if (this.getProductType() == 'model') {
                    return 'product-model';
                }
                else {
                    return 'products';
                }
            },

            getSubtitle() {
                if (this.getProductType() == 'model') {
                    return this.config.labels.subTitleModel;
                }
                else {
                    return this.config.labels.subTitle;
                }
            },

            getProductType() {
                return this.getRoot().model.get('type');
            },

            /**
             * Opens the modal then instantiates the creation form inside it.
             * This function returns a rejected promise when the popin
             * is canceled and a resolved one when it's validated.
             *
             * @return {Promise}
             */
            open() {

                const deferred = $.Deferred();
                const modal = new Backbone.BootstrapModal({
                    content: '',
                    cancelText: __('pim_common.cancel'),
                    okText: __('pim_common.save'),
                    okCloses: false,
                    illustrationClass: this.getIllustrationClass()
                });

                modal.open();
                modal.$el.addClass('modal--fullPage');

                const modalBody = modal.$('.modal-body');
                modalBody.addClass('creation');

                this.setElement(modalBody);
                this.render();

                modal.on('cancel', () => {
                    deferred.reject();
                    modal.remove();
                });

                modal.on('ok', this.confirmModal.bind(this, modal, deferred));

                return deferred.promise();
            },

            /**
             * Confirm the modal and redirect to route after save
             * @param  {Object} modal    The backbone view for the modal
             * @param  {Promise} deferred Promise to resolve
             */
            confirmModal(modal, deferred) {
                this.save().done(entity => {
                    modal.close();
                    modal.remove();
                    deferred.resolve();

                    router.redirectToRoute(this.config.editRoute);
                    messenger.notify('success', __(this.config.successMessage));
                });
            },

            /**
             * Save the form content by posting it to backend
             *
             * @return {Promise}
             */
            save() {
                const loadingMask = new LoadingMask();
                this.$el.empty().append(loadingMask.render().$el.show());

                let data = $.extend(this.getFormData(),
                    this.config.defaultValues || {});

                if (this.config.excludedProperties) {
                    data = _.omit(data, this.config.excludedProperties)
                }

                var that = this;

                return $.ajax({
                    url: Routing.generate(this.getPostRoute()),
                    type: 'POST',
                    data: JSON.stringify(data)
                }).fail(function (response) {
                    if (response.responseJSON) {
                        that.globalErrors = response.responseJSON.values;
                        this.render();
                    }
                }.bind(this))
                    .always(() => loadingMask.remove());
            },
            getPostRoute() {
                if (this.getFormData().type === 'model') {
                    return this.config.postProductModelRoute;
                } else {
                    return this.config.postProductRoute;
                }
            }
        });
    }
);
