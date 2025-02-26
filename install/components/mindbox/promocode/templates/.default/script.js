document.addEventListener('DOMContentLoaded', () => {
    const promocodeNode = document.querySelectorAll('[data-entity=mindbox-promocode]');
    const promocodeInputNode = document.querySelectorAll('[data-entity=mindbox-coupon-input]');

    if (!promocodeNode || !promocodeInputNode) {
        return;
    }

    for(let i = 0; i < promocodeInputNode.length; i++) {
        promocodeInputNode.item(i).addEventListener('change', async (event) => {
            if (event.target && event.target.value) {
                await apply(event.target.value);
                updateForm();
            }
        });

        promocodeInputNode.item(i).addEventListener('paste', async (event) => {
            setTimeout(async () => {
                if (event.target && event.target.value) {
                    await apply(event.target.value);
                    updateForm();
                }
            }, 10);
        });
    }

    for(let i = 0; i < promocodeNode.length; i++) {
        promocodeNode.item(i).addEventListener('click', async (event) => {
            if (
                event &&
                event.target
            ) {
                switch (event.target.dataset.action) {
                    case 'remove': {
                        if (event.target.dataset.coupon) {
                            await remove(event.target.dataset.coupon);
                            updateForm();
                        }
                        break
                    }
                    case 'apply': {
                        const parentNode = event.target.closest('[data-entity=mindbox-promocode]');
                        if (!promocodeInputNode) {
                            return;
                        }

                        const promocodeInputNode = parentNode.querySelector('[data-entity=mindbox-coupon-input]');
                        if (promocodeInputNode && promocodeInputNode.value) {
                            await apply(promocodeInputNode.value);
                            updateForm();
                        }
                    }
                }
            }
        })
    }


    if (typeof BX !== 'undefined' && BX.addCustomEvent) {
        BX.addCustomEvent('OnMindboxUpdatePromocode', () => {
            updatePromocodes();
        });
    }

    async function updateForm(){
        if (BX.Sale.BasketComponent) {
            BX.Sale.BasketComponent.sendRequest('recalculateAjax', {});
        } else if (BX.Sale.OrderAjaxComponent) {
            BX.Sale.OrderAjaxComponent.sendRequest();
        } else {
            location.reload();
        }
    }

    async function updatePromocodes() {
        const response = await getAll();

        if (response.status !== 'success') {
            //error
            return;
        }

        const nodeList = document.querySelectorAll('[data-entity=mindbox-promocode-list]');
        if (!nodeList) {
            return;
        }

        const promocodes = response.data;
        let promocodeText = '';
        for (let promocodesKey in promocodes) {
            let textClass = promocodes[promocodesKey]['apply'] ? 'text-muted' : 'text-danger';
            let textCoupon = promocodes[promocodesKey]['apply'] ? BX.message('MINDBOX_COUPON_APPLY') : promocodes[promocodesKey]['error'];
            const messageDelete = BX.message('MINDBOX_COUPON_DELETE');

            promocodeText += `
                <div class="mindbox-coupon-alert ${textClass}">
                    <span class="mindbox-coupon-text">
                        <strong>${promocodesKey}</strong> ${textCoupon}
                    </span>
                    <span class="close-link" data-entity="mindbox-coupon-delete" data-action="remove"
                      data-coupon="${promocodesKey}">${messageDelete}</span>
              </div>
            `;
        }

        for(let i = 0; i < promocodeInputNode.length; i++) {
            nodeList.item(i).innerHTML = promocodeText;
        }
    }

    async function remove(coupon) {
        return BX.ajax.runComponentAction('mindbox:promocode', 'remove', {
            mode: 'class',
            data: {
                coupon: coupon
            }
        })
    }

    async function apply(coupon) {
        return BX.ajax.runComponentAction('mindbox:promocode', 'apply', {
            mode: 'class',
            data: {
                coupon: coupon
            }
        })
    }

    async function getAll() {
        return BX.ajax.runComponentAction('mindbox:promocode', 'get', {
            mode: 'class',
            data: {}
        })
    }

    async function clear() {
        return BX.ajax.runComponentAction('mindbox:promocode', 'clear', {
            mode: 'class',
            data: {}
        })
    }
})