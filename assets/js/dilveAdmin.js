import swal from 'sweetalert';

async function showAlert( actionType, buttonValue ) {
    const alertConfig = {
        icon: "warning",
        dangerMode: true,
        buttons: {
            cancel: "Cancelar",
            confirm: "¡Adelante!"
        }
    };

    if (actionType === 'delete') {
        alertConfig.text = "Ojo cuidau que se borra todo!";
    } else {
        alertConfig.text = `A continuación vas a a ${buttonValue}`;
    }

    const willProceed = await swal(alertConfig).then(willDelete => willDelete);
    return willProceed;
}

async function makeAjaxRequest( action, additionalData = {} ) {
    const formData = new FormData();
    formData.append('action', action);
    formData.append('dilve_nonce', document.querySelector("#dilve_nonce").value);
    console.info(formData);
    console.info(additionalData);
    if( additionalData ) {
        for (const [key, value] of Object.entries( additionalData )) {
            formData.append( key, value );
        }
    } else {
        formData.append('dilve_nonce', document.querySelector("#dilve_nonce").value);
    }
    console.info(formData);
    console.info(ajaxurl);
    const response = await fetch( ajaxurl, {
        method: "POST",
        credentials: "same-origin",
        body: formData
    });
    console.info(response);
    try {
        const jsonResponse = await response.json();
        console.info(jsonResponse);
        if (jsonResponse.success) {
            return jsonResponse;
        } else {
            console.error( "Request was not successful" );
            return null;
        }
    } catch ( error ) {
        console.error( "Error parsing JSON: ", error );
        console.error( "Raw response: ", await response.text() );
    }
}

async function updateProgress( action, dilveContainer ) {
    let offset = 0;
    const batchSize = 3; // Process 10 records at a time

    while (true) {
        const additionalData = {
            'offset': offset,
            'batch_size': batchSize
        };
        const response = await makeAjaxRequest(action, additionalData);
        console.info(response);
        console.info(response.data);
        const JsonData = typeof response.data === 'string' ? JSON.parse(response.data) :
              (response.data instanceof Object ? response.data : '');

        // Logging the type for debugging
        const dataType = typeof response.data === 'string' ? 'is string' :
                        (response.data instanceof Object ? 'is Object' : 'is neither');
        console.info(dataType);

        console.info(JsonData);
        if ( !response.success ) {
            console.error('Error');
            dilveContainer.innerHTML = 'Error!';
            break;
        }
        if ( JsonData.message ) {
            dilveContainer.innerHTML += `<div>${JsonData.message}</div>`;
        }
        dilveContainer.innerHTML += `<div>Batch processed. Current offset: ${offset} - Progress ${JsonData.progress}</div>`;
        dilveContainer.innerHTML += JsonData.eans.forEach( (ean) =>{
            return `<div>EAN ${ean}</div>`;
        });
        dilveContainer.scrollTop = dilveContainer.scrollHeight;
        if ( action == "dilve_scan_products" || action == "dilve_hello_world" ) {
            if ( !JsonData.hasMore || JsonData.hasMore == 0 ) {
                dilveContainer.innerHTML += `<div>All products processed.</div>`;
                break;
            }
            offset += batchSize;
        }
    }
}


document.addEventListener("DOMContentLoaded", function() {
    const dilveContainer = document.querySelector("[data-container='dilve']");
    const terminalElement = document.querySelector(".terminal");

    if(terminalElement) terminalElement.style.display = "none";
    const actions = [
        { buttonName: 'scan_products', action: 'dilve_scan_products', type: '' },
        { buttonName: 'hello_world', action: 'dilve_hello_world', type: '' },
    ];
    console.info( actions );
    actions.forEach( async ({ buttonName, action, type }) => {
        document.querySelector( `[name='${buttonName}']` ).addEventListener( "click" , async (event) => {
            event.preventDefault();
            const buttonElement = document.querySelector( `[name='${buttonName}']` );
            const willProceed = await showAlert( type, buttonElement.value );
            console.info( buttonElement, 'line 111');
            if (willProceed) {
                terminalElement.style.display = "block";
                updateProgress( action, dilveContainer );
            }
        });
    });
});