(function() {
    Date.prototype.toYMD = Date_toYMD;
    function Date_toYMD() {
        let year, month, day;
        year = String(this.getFullYear());
        month = String(this.getMonth() + 1);
        if (month.length === 1) {
            month = "0" + month;
        }
        day = String(this.getDate());
        if (day.length === 1) {
            day = "0" + day;
        }
        return year + "-" + month + "-" + day;
    }
})();
let _get = function getUrlParameter(param) {
    let sPageURL = window.location.search.substring(1);
    let sURLVariables = sPageURL.split('&');
    let sParameterName;
    let i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === param) {
            return sParameterName[1] === undefined ? true : decodeURIComponent(sParameterName[1]);
        }
    }

    return '';
};
function updateUrl(urlPath){
    window.history.pushState('Reloading...','Карта', urlPath);
}

let myMap;
let objectManager;
let availableDates = undefined;
let baseUrl = window.location.href;
baseUrl = baseUrl.split('?');
baseUrl = baseUrl[0];

function available(date) {
    let dmy = date.toYMD();
    if (availableDates !== undefined) {
        return ($.inArray(dmy, availableDates) !== -1);
    } else{
        return true;
    }
}

$(function () {

    let statusSelector = $('select[name="statuses"]');

    $('select[name="sale"]').on('change', function () {
        let option = $(this).find('option:selected');
        let statuses = option.data('statuses');

        statusSelector.empty();
        statusSelector.append('<option value="">Все</option>');

        if (statuses !== undefined && statuses !== '') {
            statuses = statuses.split('|');
            statuses.forEach(function (value) {
                statusSelector.append('<option value="'+value+'">'+value+'</option>');
            });
            statusSelector.prop('disabled', false);
        } else{
            statusSelector.prop('disabled', true);
        }
    });

    $('input[name="date"]').datepicker({
        format: 'yyyy-mm-dd',
        language: 'ru',
        weekStart: 1,
        clearBtn: true,
        beforeShowDay: function(dt) {
            return available(dt);
        }
    });

    $(document).on('click', '.item', function () {
        $(".item").removeClass('active');
        $(this).addClass('active');

        let lat = parseFloat($(this).data('lat'));
        let long = parseFloat($(this).data('long'));

        myMap.panTo([lat, long]);
    });

    $('select').on('change', function () {
        $('input[name="date"]').val('');

        if ($(this).is('[name="statuses"]')) {
            let selMulti = $.map($(this).find('option:selected'), function (el) {
                return $(el).val();
            });
            $('input[name="status"]').val(selMulti.join("|"));
        }

        updateDate();
    });

    $('form').on('submit', function (e) {
        e.preventDefault();

        let sale = $('select[name="sale"]').val();
        let status = $('input[name="status"]').val();
        let date = $('input[name="date"]').val();

        updateUrl(`${baseUrl}?sale=${sale}&status=${status}&date=${date}`);
        updatePoints();
    })
});

ymaps.ready(init);
function init() {
    myMap = new ymaps.Map("map", {
        center: [55.7468993, 37.5880382],
        zoom: 10
    }, {
        searchControlProvider: 'yandex#search'
    });
    objectManager = new ymaps.ObjectManager({
        clusterize: true,
        gridSize: 32
    });
    objectManager.objects.options.set('preset', 'islands#blueDotIcon');
    objectManager.clusters.options.set('preset', 'islands#blueClusterIcons');
    myMap.geoObjects.add(objectManager);
    updatePoints();
}

function updatePoints() {
    let sale = $('select[name="sale"]').val();
    let status = $('input[name="status"]').val();
    let date = $('input[name="date"]').val();

    $.ajax({
        url: `/get_map.php?sale=${sale}&status=${status}&date=${date}`
    }).done(function(data) {

        let list = $('#list');

        list.empty();
        if (data.length === undefined) {
            data.features.forEach(function (value, index) {
                let pointData = value.point;
                data.features[index].properties = {
                    balloonContentHeader: pointData.name,
                    balloonContent: pointData.person + '<br>' + pointData.comment,
                    balloonContentFooter: pointData.address,
                    iconCaption: pointData.name,
                };
                addToList(pointData);
            });
            objectManager.removeAll();
            objectManager.add(data);
        } else{
            list.append('<p class="text-center">Ничего не найдено</p>');
        }
    });
}

function updateDate() {
    let sale = $('select[name="sale"]').val();
    let status = $('input[name="status"]').val();

    $.ajax({
        url: `/get_dates.php?sale=${sale}&status=${status}`
    }).done(function(data) {
        availableDates = data;
        $('input[name="date"]').datepicker('update');
    });
}

function addToList(point) {
    $('#list').append('<div class="item" data-lat="' + point.lat + '" data-long="' + point.lng + '">\n' +
        '    <p>' + point.name + '</p>\n' +
        '    <p>' + point.person + '</p>\n' +
        '    <p>' + point.address + '</p>\n' +
        '    <p>' + point.status + ' ' + point.date + '</p>\n' +
        '</div>');
}