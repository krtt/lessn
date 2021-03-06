form = $('#loginform');

form.submit(function (event) {
    event.preventDefault();
    console.log(form.serializeArray());

    $.ajax({
        url: form.attr('action'), // url where to submit the request
        type: "POST", // type of action POST || GET
        // dataType: 'json', // data type
        data: form.serializeArray(), // post data || get data

        success: function (data) {
            // you can see the result from the console
            // tab of the developer tools
            console.log(data[0] === false);
            console.log(data[1]);

            if (data[0] === false) {
                $('.loginerror').html(data[1])
            } else {
                location.reload(true);
                // resetHome();
                // resetToolbar();
            }
        }
    });
});