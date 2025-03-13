document.addEventListener('DOMContentLoaded', function (event) {
    const nodes = {
        mindboxForms: document.querySelectorAll('.mindbox-basket-bonus--active'),
        bonusForms: document.querySelectorAll('.mindbox-basket-bonus__promo-form')
    }
    window.mindboxNodes = nodes;

    if (!!nodes.mindboxForms && nodes.mindboxForms.length > 0) {
        mindboxCalculate();

        nodes.mindboxForms.forEach(function (element) {
            const bonusForm = element.querySelector('.mindbox-basket-bonus__promo-form');
            const submitButton = bonusForm.querySelector('.mindbox-basket-bonus__promo-submit');

            submitButton.addEventListener('click', function (event) {
                event.preventDefault();
                const applyBonusValue = bonusForm.querySelector('.mindbox-basket-bonus__promo').value;

                if (applyBonusValue > 0 && !bonusForm.classList.contains('accepted')) {
                    mindboxApply(applyBonusValue, bonusForm, submitButton)
                } else if (bonusForm.classList.contains('accepted')) {
                    mindboxCancelApply();
                }
            })
        })
    }
});

document.addEventListener('DOMContentLoaded', function (event) {
    const components = document.querySelectorAll('.mindbox-basket-bonus--active');

    components.forEach(function (element) {
        const nodes = {
            mindboxForm: element.querySelector('.mindbox-basket-bonus__promo-form'),
            container: element.querySelector('.mindbox-basket-bonus__promo-input-fields-container label'),
            formInput: element.querySelector('input[name=mindbox-bonus-value]')
        };

        nodes.formInput.addEventListener('input', function (event) {
            const inputData = nodes.formInput.value;
            const check = /^\d+$/.test(inputData);

            if (!check) {
                nodes.formInput.value = nodes.formInput.value.replace(/\D/g, '');
            }

            if (check) {
                nodes.formInput.value = nodes.formInput.value.replace(/^0+/, '');
            }

            if (nodes.formInput.value.length > 0 && window.mindboxTotalBonus > 0) {
                if (
                    !nodes.mindboxForm.classList.contains('active')
                    && !nodes.mindboxForm.classList.contains('error')
                    && !nodes.mindboxForm.classList.contains('accepted')
                ) {
                    nodes.mindboxForm.classList.add('active');
                }
            } else {
                if (nodes.mindboxForm.classList.contains('active')) {
                    nodes.mindboxForm.classList.remove('active');
                }
            }
            if (nodes.formInput.value > window.mindboxTotalBonus) {
                if (!nodes.mindboxForm.classList.contains('error')) {
                    nodes.mindboxForm.classList.add('error')
                    nodes.mindboxForm.classList.remove('active');
                }
            } else {
                if (nodes.mindboxForm.classList.contains('error')) {
                    nodes.mindboxForm.classList.remove('error')
                    nodes.mindboxForm.classList.add('active');
                }
            }
        })

        nodes.container.addEventListener('click', function (event) {
            event.preventDefault();

            if (!nodes.mindboxForm.classList.contains('accepted')) {
                nodes.formInput.value = '';
                nodes.mindboxForm.classList.remove('active');
                nodes.mindboxForm.classList.remove('error');
                mindboxCancelApply();
            }
        })
    })
})

if (typeof window[mindboxCalculate] !== 'function') {
    function mindboxCalculate() {
        return new Promise((resolve, reject) => {
            BX.ajax.runComponentAction('mindbox:bonuses', 'calculate', {
                mode: 'class',
                timeout: 10,
                method: 'POST',
            }).then(function (response) {
                if (response.data.available && response.data.available > 0) {
                    window.mindboxTotalBonus = response.data.available;

                    window.mindboxNodes.mindboxForms.forEach(function (element) {
                        const nodes = {
                            bonusAmountBlocks: element.querySelectorAll('.mindbox-basket-bonus__bonus-amount'),
                            availableBlocks: element.querySelectorAll('.mindbox-basket-bonus__promo-info-num[data-type="available"]'),
                            earnedBlocks: element.querySelectorAll('.mindbox-basket-bonus__promo-info-num[data-type="earned"]'),
                        }

                        nodes.bonusAmountBlocks.forEach(function (item) {
                            item.innerHTML = response.data.total;
                        })

                        nodes.availableBlocks.forEach(function (item) {
                            item.innerHTML = response.data.available;
                        })

                        if (response.data.available > 0) {
                            document.querySelectorAll('.mindbox-basket-bonus__promo-form').forEach(function (element) {
                                element.querySelector('.mindbox-basket-bonus__promo').removeAttribute('disabled');
                            })
                        }

                        nodes.earnedBlocks.forEach(function (item) {
                            item.innerHTML = response.data.earned;
                        })
                    })
                }

                resolve(response.data.total);
            }).catch(function () {
                resolve(false);
            });
        })
    }
}

if (typeof window[mindboxApply] !== 'function') {
    function mindboxApply(bonusAmount) {
        console.log('mindboxApply')
        BX.ajax.runComponentAction('mindbox:bonuses', 'apply', {
            mode: 'class',
            timeout: 10,
            method: 'POST',
            data: {
                bonuses: bonusAmount,
            }
        }).then(function (response) {
            if (window.isAjaxObj.IS_AJAX === 'Y') {
                if (BX.Sale.BasketComponent) {
                    BX.Sale.BasketComponent.sendRequest('recalculateAjax', {});
                    mindboxCalculate();
                } else if (BX.Sale.OrderAjaxComponent) {
                    BX.Sale.OrderAjaxComponent.sendRequest();
                    mindboxCalculate();
                } else {
                    location.reload();
                }
            } else {
                location.reload();
            }
        })
    }
}

if (typeof window[mindboxCancelApply] !== 'function') {
    function mindboxCancelApply() {
        BX.ajax.runComponentAction('mindbox:bonuses', 'cancel', {
            mode: 'class',
            timeout: 10,
            method: 'POST',
        }).then(function () {
            if (window.isAjaxObj.IS_AJAX === 'Y') {
                if (BX.Sale.BasketComponent) {
                    BX.Sale.BasketComponent.sendRequest('recalculateAjax', {});
                    mindboxCalculate();
                } else if (BX.Sale.OrderAjaxComponent) {
                    BX.Sale.OrderAjaxComponent.sendRequest();
                    mindboxCalculate();
                } else {
                    location.reload();
                }
            } else {
                location.reload();
            }
        })
    }
}

if (typeof window[mindboxCalculateBasketChange] !== 'function') {
    function mindboxCalculateBasketChange() {
        const asyncCalculate = async () => {
            const result = await mindboxCalculate();

            const enteredAmount = document.querySelector('.mindbox-basket-bonus__promo').value;

            if (result) {
                if (window.mindboxTotalBonus <= enteredAmount) {
                    mindboxApply(window.mindboxTotalBonus)
                }
            } else {
                mindboxCancelApply();
            }
        }

        asyncCalculate();
    }
}

if (typeof window[refreshForms] !== 'function') {
    function refreshForms() {
    window.mindboxNodes.bonusForms.forEach(function (bonusForm) {

        if (bonusForm.classList.contains('accepted')) {
            bonusForm.classList.remove('accepted');

            if (bonusForm.classList.contains('error')) {
                bonusForm.classList.remove('error');
            }
            const submitButton = bonusForm.querySelector('.mindbox-basket-bonus__promo-submit');
            const formInput = bonusForm.querySelector('input[name=mindbox-bonus-value]');

            formInput.value = '';
            submitButton.value = BX.message['BONUS_ACCEPT'];
        }
    })

    if (window.isAjaxObj.IS_AJAX === 'Y') {

        if (BX.Sale.BasketComponent) {
            BX.Sale.BasketComponent.sendRequest('recalculateAjax', {});
        } else if (BX.Sale.OrderAjaxComponent) {
            BX.Sale.OrderAjaxComponent.sendRequest();
        } else {
            location.reload();
        }
    } else {
        location.reload();
    }
    }
}
BX.addCustomEvent('onCalculateBasketChange', BX.delegate(mindboxCalculateBasketChange));
