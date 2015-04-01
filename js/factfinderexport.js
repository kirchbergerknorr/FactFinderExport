jQuery(function($){
    var $cronrow = $('#row_kirchbergerknorr_factfinderexport_cron');
    if ($cronrow.length) {

        $cronrow.after('<tr id="row_kirchbergerknorr_factfinderexport_progress"><td class=label>Progress</td><td class="value"><span id="ff_last">...</span><br><span id="ff_status">...</span><br><a href="#" id="factfinder_restart">Restart</a> | <a href="#" id="factfinder_start">Continue</a> | <a href="#" id="factfinder_stop">Stop</a></td></tr>');

        $('body').on('click', "#factfinder_start", function(e){
            e.preventDefault();
            $.get('/factfinder.php');
        });

        $('body').on('click', "#factfinder_restart", function(e){
            e.preventDefault();
            $.get('/factfinder.php?restart=1', function(){
                update();
            });
        });

        $('body').on('click', "#factfinder_stop", function(e){
            e.preventDefault();
            $.get('/factfinder.php?stop=1', function(){
                $('#ff_status').html('Stopped');
            });
        });

        function update() {
            $.get('/media/factfinder.csv.run', function(data){
                var count = data;

                setInterval(function(){
                    $.get('/media/factfinder.csv.last', function(data){
                        if (data && count) {
                            var percent = Math.round(data / count * 100);
                            var html = percent + '% | ' + data + ' from ' + count;
                            $('#ff_status').html(html);
                        }
                    }).fail(function() {
                        $('#ff_status').html('Not running');
                    });
                }, 1000)
            }).fail(function() {
                $('#ff_status').html('Not running');
            });
        };

        $.get('/factfinder.php?last', function(data){
            $('#ff_last').html(data);
            update();
        });
    }
});