</div>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.3.1/jquery.min.js"></script>
<script src="assets/js/card/dist/jquery.card.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/js/bootstrap.min.js" integrity="sha384-ChfqqxuZUCnJSK3+MXmPNIyE6ZbWh2IMqE241rYiqJxyMiZ6OW/JmZQ5stwEULTy" crossorigin="anonymous"></script>
<script type="text/javascript" src="https://cdn.conekta.io/js/latest/conekta.js"></script>
<script>

    Conekta.setPublicKey('<?php echo $keyPublic; ?>');

    (function($){

        $('form').card({
            // a selector or DOM element for the container
            // where you want the card to appear
            container: '.card-wrapper', // *required*
        })

        $('.paymentMethod').click(function(){
            let type = $(this).attr('data-type')
            if (type === 'oxxo')
                $('.method-oxxo').show()
            if (type === 'spei')
                $('.method-spei').show()
            if (type === 'credit')
                $('.method-credit').show()
            $(".payent").not(".method-"+type).hide();
        })

        $('form').submit(function(e){
            e.preventDefault();
            const el = $(this)
            const payment_method = $(el).parent().find("input[name='payment_method']").val();
            let alertMsj = $('.alert')

            $(alertMsj).empty()
            $(alertMsj).hide()

            if(payment_method === undefined){

                let expire = $("input[name='expiry']").val();

                loadExpiration(expire,el)

                Conekta.Token.create(el, function (token) {
                    el.append($('<input name="conektaTokenId" id="conektaTokenId" type="hidden">').val(token.id));
                    sendAjax(el)
                }, function (response) {
                    $(alertMsj).addClass('alert-info').text(response.message_to_purchaser).show()
                })

            }else{
                sendAjax(el, payment_method)
            }

        })


        function sendAjax(el, payment_method = false){
            $.ajax({
                type: "post",
                url: "payment.php",
                dataType: "json",
                data: $(el).serialize(),
                beforeSend: function() {
                    $(el).parent().find('input[type="submit"]').prop('disabled', true)
                    $('body').css('cursor', 'wait')
                },
                success: function(r) {
                    $('body').css('cursor', 'default')
                    console.log(r)
                    if (r.status){
                        if(payment_method){
                            $(el).hide()
                            $(el).next('div').show()
                            fillCash(payment_method, r.data)
                        }

                        if (r.data.url){
                            window.location.replace(r.data.url)
                        }

                    }else{
                        $('.alert').addClass('alert-info').text(r.msj).show()
                    }
                }
            })
        }


        function loadExpiration(exp, $form){
            exp = exp.replace(/\s/g,'').split('/')

            let month
            let year

            if (exp[1]){
                month = exp[0]
                year = exp[1]
            }

            $form.append($('<input data-conekta="card[exp_month]" type="hidden">').val(month))

            $form.append($('<input data-conekta="card[exp_year]" type="hidden">').val(year))
        }

        function fillCash(method, data){
            if (method === 'oxxo_cash'){
                $('#cash_oxxo h2.mount b').text(data.amount)
                $('#cash_oxxo .reference').text(data.charges.data[0].payment_method.reference);
                $('#cash_oxxo sup').text(data.charges.data[0].currency);
            }else{
                $('#cash_spei h2.mount b').text(data.amount)
                $('#cash_spei .clabe').text(data.charges.data[0].payment_method.clabe);
                $('#cash_spei sup').text(data.charges.data[0].currency);
            }
        }

    })(jQuery)
</script>
</body>
</html>