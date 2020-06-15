export default class SLMKForm {
    constructor(data = {}) {
        this.submissionEndpoint = `${data.formEndpoint}/form/submission`;
        this.brandSlug = data.brandSlug;
        this.id = data.formId;
        this.selector = `#slmk_form_${data.formId}`;
        this.$form = $(this.selector);
        this.formData = null;
        this.$formErrors = $('#slmk_form_errors');
        this.$submitButton = this.$form.find('button');
        this.init();
    }

    init() {
        $(document).ready(() => {
            this.submissionListener();
        });
    }

    submitSLMKForm() {
        return $.ajax({
            url: this.submissionEndpoint,
            method: 'POST',
            data: this.formData,
            processData: false,
            contentType: false,
            crossDomain: true,
            dataType:"json"
        });
    }

    compileFormData() {
        this.formData = new FormData;
        this.formData.append('form_id', this.id);
        this.formData.append('brand', this.brandSlug);

        const serializedData = this.$form.serializeArray();
        serializedData.map(item => {
            const data = {
                id: item.name,
                value: item.value
            };
            this.formData.append(`fields[${item.name}]`, JSON.stringify(data));
        });

        if(this.$form.find('[type=file]').length) {
            this.$form.find('[type=file]').each((idx,el) => {
                const name = el.getAttribute('name');
                this.formData.append(`files[${name}]`, el.files[0]);
            });
        }
    }

    submissionListener() {
        this.$form.submit(e => {
            e.preventDefault();
            this.hideErrors();
            this.compileFormData();

            this.submitSLMKForm().done(() => {
                this.handleDone();
            }).fail((jqXHR, textStatus, errorThrown) => {
                this.handleFail(jqXHR, textStatus, errorThrown);
            });
        });
    }

    handleDone() {
        this.$form.hide();
        $('#slmk_form_submitted').fadeIn();
        this.scrollTop();
    }

    handleFail(jqXHR, textStatus, errorThrown) {
        try {
            const errorsArray = Object.values(jqXHR.responseJSON.errors);
            const errors = [].concat.apply([], errorsArray);
            this.showErrors(errors);
            this.scrollTop();
        } catch(e) {
            console.error(e);
        }
    }

    appendError(errorHtml) {
        this.$formErrors.append(errorHtml);
    }

    showErrors(errorsArray) {
        errorsArray.map(error => {
            const errorHtml = `<li class="text-danger">${error}</li>`;
            this.appendError(errorHtml);
        });
        this.$formErrors.show();
    }

    hideErrors() {
        this.$formErrors.empty();
        this.$formErrors.hide();
    }

    scrollTop() {
        $("html, body").animate({ scrollTop: "0px" });
    }

    setLoading() {
        this.$submitButton.attr('disabled', true);
        this.$submitButton.find('span').show();
    }

    unsetLoading() {
        this.$submitButton.removeAttr('disabled');
        this.$submitButton.find('span').hide();
    }

}
