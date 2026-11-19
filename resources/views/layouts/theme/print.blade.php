{{--
    Impresión de tickets y comprobantes: todo pasa por sgPrint(url).
    El método se elige en Configuración → Impresión (Company::PRINT_METHODS);
    para sumar uno nuevo (p. ej. un programa de impresión local) se agrega
    otra entrada en SG_PRINT_METHODS.
--}}
<script>
    window.SG_PRINT_METHOD = @json(\App\Models\Company::printMethod());

    window.SG_PRINT_METHODS = {
        // Impresión web: carga el documento en un marco oculto (sin abrir pestañas ni ventanas)
        // y abre el cuadro de impresión del navegador.
        browser: function (url) {
            let frame = document.getElementById('sg-print-frame');
            if (!frame) {
                frame = document.createElement('iframe');
                frame.id = 'sg-print-frame';
                frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
                document.body.appendChild(frame);
            }

            frame.onload = function () {
                frame.onload = null;
                try {
                    // El ticket de venta se imprime solo al cargar (marca SG_SELF_PRINT);
                    // imprimirlo de nuevo aquí sacaba dos copias. Lo demás (facturas, PDF) necesita print().
                    if (!frame.contentWindow.SG_SELF_PRINT) {
                        frame.contentWindow.focus();
                        frame.contentWindow.print();
                    }
                } catch (e) {
                    window.open(url, '_blank');
                }
            };
            frame.src = url;
        },

        // PrinterNinja: el sistema arma el ticket y el programa lo manda a la impresora (print://).
        // Tickets de venta y de compra, comprobantes de pago de crédito y facturas; lo demás sigue por el navegador.
        ninja: function (url) {
            // [dirección del documento, dirección de sus datos para PrinterNinja]
            const documentos = [
                [/(\/pos\/ventas\/[^\/?#]+\/ticket)(?=[?#]|$)/, '$1-ninja'],
                [/(\/compras\/[^\/?#]+\/ticket)(?=[?#]|$)/, '$1-ninja'],
                [/(\/creditos\/pagos\/[^\/?#]+\/comprobante)(?=[?#]|$)/, '$1-ninja'],
                [/(\/invoice-(?:legal|summary)\/[^\/?#]+)(?=[?#]|$)/, '$1/ninja'],
            ];
            const doc = documentos.find(function (d) { return d[0].test(url); });
            if (!doc) {
                window.SG_PRINT_METHODS.browser(url);
                return;
            }

            const datos = url.replace(doc[0], doc[1]);
            fetch(datos, { credentials: 'same-origin', cache: 'no-store' })
                .then(function (r) {
                    if (!r.ok) throw new Error('HTTP ' + r.status);
                    return r.text();
                })
                .then(function (data) {
                    let frame = document.getElementById('sg-ninja-frame');
                    if (!frame) {
                        frame = document.createElement('iframe');
                        frame.id = 'sg-ninja-frame';
                        frame.style.cssText = 'position:fixed;right:0;bottom:0;width:0;height:0;border:0;';
                        document.body.appendChild(frame);
                    }
                    frame.src = 'print://' + data.trim();
                })
                .catch(function () {
                    // Sin los datos no se imprime nada; mejor el ticket del navegador
                    window.SG_PRINT_METHODS.browser(url);
                });
        },
    };

    window.sgPrint = function (url) {
        if (!url) return;
        const print = window.SG_PRINT_METHODS[window.SG_PRINT_METHOD] || window.SG_PRINT_METHODS.browser;
        print(url);
    };
</script>
