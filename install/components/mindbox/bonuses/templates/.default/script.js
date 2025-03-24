document.addEventListener("DOMContentLoaded", function () {
    const componentName = 'mindbox:bonuses';

    const selectorMainContainer = '[data-entity="mindbox-bonuses"]';
    const selectorTotal = '[data-entity="mindbox-bonuses-total"]';
    const selectorAvailable = '[data-entity="mindbox-bonuses-available"]';
    const selectorEarned = '[data-entity="mindbox-bonuses-earned"]';
    const selectorBonusValue = '[data-entity="mindbox-bonuses-value"]';
    const selectorApplyButton = '[data-entity="mindbox-bonuses-apply"]';
    const selectorErrors = '[data-entity="mindbox-bonuses-errors"]';

    document.querySelectorAll(selectorMainContainer).forEach(async function (bonusContainer) {
        disableBonus(bonusContainer)
        loadBonus(bonusContainer)
        // Применение бонусов
        bonusContainer.querySelector(selectorApplyButton)
            .addEventListener('click', async function(event){
                event.preventDefault();
                const bonusValue = bonusContainer.querySelector(selectorBonusValue).value;
                if (bonusValue > 0 && bonusContainer.dataset.bonusStatus !== 'error') {
                    await apply(bonusValue);
                    bonusContainer.dataset.bonusStatus = 'accepted';
                } else {
                    await clear();
                }
                await updateForm();
            });
        // Валидация поля бонусов
        bonusContainer.querySelector(selectorBonusValue)
            .addEventListener('input', function (event) {
                const value = event.target.value;
                const check = /^\d+$/.test(event.target.value);
                if (!check) {
                    event.target.value = value.replace(/\D/g, '');
                }

                if (check) {
                    event.target.value = value.replace(/^0+/, '');
                }
            });
    });

    async function updateForm(){
        if (BX.Sale.BasketComponent) {
            BX.Sale.BasketComponent.sendRequest('recalculateAjax', {});
            loadBonus();
        } else if (BX.Sale.OrderAjaxComponent) {
            BX.Sale.OrderAjaxComponent.sendRequest();
            loadBonus();
        } else {
            location.reload();
        }
    }
    async function get() {
        return BX.ajax.runComponentAction(componentName, 'get', {
            mode: 'class',
        });
    }

    function disableBonus(bonusContainer) {
        bonusContainer.querySelector(selectorApplyButton).setAttribute('disabled', 'disabled');
        bonusContainer.querySelector(selectorBonusValue).setAttribute('disabled', 'disabled');
    }
    function enableBonus(bonusContainer) {
        bonusContainer.querySelector(selectorApplyButton).removeAttribute('disabled');
        bonusContainer.querySelector(selectorBonusValue).removeAttribute('disabled');
    }

    function loadBonus(bonusContainer) {
        const asyncLoad = async () => {
            const response = await get();
            if (response.data.available && response.data.available > 0) {
                bonusContainer.querySelector(selectorTotal).innerText = response.data.total;
                bonusContainer.querySelector(selectorAvailable).innerText = response.data.available;
                bonusContainer.querySelector(selectorEarned).innerText = response.data.earned;

                if (response.data.available > 0) {
                    enableBonus(bonusContainer)
                }
            }
        }

        asyncLoad();
    }


    async function apply(value) {
        return BX.ajax.runComponentAction(componentName, 'apply', {
            mode: 'class',
            data: {
                bonus: value
            }
        })
    }
    async function clear() {
        return BX.ajax.runComponentAction(componentName, 'cancel', {
            mode: 'class',
        })
    }

    function mindboxCalculateBasketChange() {
        document.querySelectorAll(selectorMainContainer).forEach(async function (bonusContainer) {
            loadBonus(bonusContainer);
        });
    }
    BX.addCustomEvent('onCalculateBasketChange', BX.delegate(mindboxCalculateBasketChange));
});

