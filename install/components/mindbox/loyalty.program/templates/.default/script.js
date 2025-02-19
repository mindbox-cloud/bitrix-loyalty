document.addEventListener('DOMContentLoaded', function(){

    const moreButton = document.querySelector('#mindbox-bonus-more');
    if (!moreButton) {
        return;
    }

    let submitted = false;

    moreButton.addEventListener('click', function () {
        if (submitted) {
            return;
        }

        submitted = true;

        const page = parseInt(moreButton.dataset.page) + 1;

        const request = BX.ajax.runComponentAction(bonusHistoryData.componentName, 'page', {
            mode: 'class',
            signedParameters: bonusHistoryData.signedParameters,
            data: {
                page: page
            }
        });

        request.then(function (response) {
            if (response.data.type === 'error') {
                moreButton.style.display = 'none';
            } else if (response.data.type === 'success') {
                moreButton.dataset.page = response.data.page;

                let html = '';
                if (response.data.history) {
                    response.data.history.forEach(item => {
                        html += `
                        <tr>
                            <td>${item.start}</td>
                            <td>${item.size}</td>
                            <td>${item.name}</td>
                            <td>${item.end}</td>
                        </tr>
                        `;
                    })
                } else {
                    moreButton.style.display = 'none';
                }

                const contentBody = document.querySelector('#mindbox-bonus-history tbody');
                if (contentBody) {
                    contentBody.insertAdjacentHTML('beforeend', html);
                }

                if (response.data.more === false) {
                    moreButton.dataset.page = response.data.page;
                }
            }

            submitted = false;
        });
    });
});