const ProfileCompleteForm = {
    form: null,
    alertDiv: null,

    init: function (formSelector) {
        if (!formSelector) {
            return;
        }

        this.form = document.querySelector(formSelector);

        if (!this.form) {
            return;
        }

        this.alertDiv = this.form.querySelector('.alert');

        let t = this;
        this.form.addEventListener('submit', function (e) {
            e.preventDefault();
            t.sendForm();
        });
    },

    sendForm: function () {
        let t = this;
        let formData = new FormData(this.form);
        BX.ajax.runComponentAction('mindbox:profile.complete', 'save', {
            mode: 'class',
            timeout: 10,
            method: 'POST',
            data: formData
        })
            .then(function(res) {
                if (!t.alertDiv || !res.data.message) {
                    return;
                }

                t.alertDiv.innerHTML = res.data.message;

                if (res.data.status === 'success') {
                    t.alertDiv.classList.remove('alert-danger');
                    t.alertDiv.classList.add('alert-success');
                } else {
                    t.alertDiv.classList.remove('alert-success');
                    t.alertDiv.classList.add('alert-danger');
                }
            });
    }
};