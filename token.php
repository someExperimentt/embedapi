<?php

$token = file_get_contents("https://analyticsconfig.invenire.com.au/dashboards/testtoken");
$token = json_decode($token,true);
$token = $token["accessToken"];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <title>Embed API Demo</title>
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <style>
        body{
            margin: 0;
            padding: 0;
            background-color: #2f4459;
        }
        header{
            width: 100%;
            height: 70px;
            background-color: #222930;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        #inputContainer{
            display: flex;
        }
        #view-selector , #date-range-selector-1-container>div{
            display: flex;
        }
        #view-selector>table{
            table-layout: auto;
        }
        .inputContainer{
            height: 100%;
            margin: 0;
            padding: 0;
            color: aliceblue;
        }
        #chartsContainer{
            display: flex;
            flex-wrap: wrap;
        }
        .charts{
            margin-right: 20px;
            margin-left: 10px;
            margin-bottom: 40px;
        }
        .selectedTableRow{
            background-color: rgb(153, 201, 219);
        }
        .headerCell{
            width: 150px;
            vertical-align: middle!important;
            background-color: #E8F6FF;
            padding: 5px!important;
            font-size: 15px!important;
        }
        .tableCell{
            height: 20px;
            font-size: 13px!important;
            text-align: center;
            vertical-align: middle!important;
            padding: 5px!important;
        }


    </style>
</head>
<body>

<!-- Step 1: Create the containing elements. -->
<header>
    <div id="inputContainer">
    <section id="auth-button" class="inputContainer"></section>
    <section id="view-selector" class="inputContainer"></section>
    <section id="date-range-selector-1-container" class="inputContainer"></section>
    </div>
</header>
<div id="chartsContainer">
    <section id="sessionPageViewChart" class="charts"></section>
    <section id="geoSessionChart" class="charts"></section>
    <section id="userTypeChart" class="charts"></section>
    <section id="countryTable" class="charts"></section>
    <section id="userSessionChart" class="charts" style=" height: 500px;"></section>
    <section id="searchEngineChart" class="charts"></section>
</div>
<!-- Step 2: Load the library. -->

<script>
    (function(w,d,s,g,js,fjs){
        g=w.gapi||(w.gapi={});g.analytics={q:[],ready:function(cb){this.q.push(cb)}};
        js=d.createElement(s);fjs=d.getElementsByTagName(s)[0];
        // TODO: Should point relative path
        js.src='./platform.js';
        fjs.parentNode.insertBefore(js,fjs);js.onload=function(){g.load('analytics')};
    }(window,document,'script'));
</script>
<script src="data-range-selector.js"></script>
<script>

    google.load = google.load || google.charts.load;
    google.charts.load('current', {'packages':['corechart','bar']});

    let initialDate = {'start-date' : '90daysAgo', 'end-date' : 'yesterday'};

    gapi.analytics.ready(function() {

        // TODO: Should be assigned
        const CLIENT_ID = 'Insert your client ID here';

        /**
         * Authorize the user immediately if the user has already granted access.
         * If no access has been created, render an authorize button inside the
         * element with the ID "auth-button".
         */
        /* TODO: Production mode should have this method instead of authorize method below. Using access_token is a security flaw
        gapi.analytics.auth.authorize({
            container: 'auth-button',
            clientid: 'REPLACE WITH YOUR CLIENT ID'
        });*/


        // TODO: This method should be removed.
        gapi.analytics.auth.authorize({
            'serverAuth': {
                'access_token': "<?php echo $token ?>"
            }
        });

        // TODO: Should contain code when user needs authorization. gapi.analytics.auth.authorize() or redirect can be used
        // TODO: if something is wrong with the Client ID
        gapi.analytics.auth.on('needsAuthorization', function() {
            console.error('Authorization is not established');
        });


        gapi.analytics.auth.on('success', function(response) {
            viewSelector.execute();
        });

        /*----- dateRangeSelector and its Events */
        const dateRangeSelector = new gapi.analytics.ext.DateRangeSelector({
            container: 'date-range-selector-1-container'
        }).set({
            'start-date': initialDate["start-date"],
            'end-date': initialDate["end-date"]
        }).execute();


        dateRangeSelector.on('change', function(data) {
            console.log("Data Changed: Current Data");
            console.log(data);
            dateChangeEventRegister.forEach(function (elem) {
                elem.emit('DateChanged',data);
            })

        });

        /*----- End of dateRangeSelector and its Events */




        /* ---- sessionPageViewChart and its Events  -----*/

        const sessionPageViewChart = new gapi.analytics.googleCharts.DataChart({
            reportType: 'ga',
            query: {
                'dimensions': 'ga:date',
                'metrics': 'ga:sessions,ga:pageviews',
                'start-date': initialDate["start-date"],
                'end-date': initialDate["end-date"]
            },
            chart: {
                type: 'LINE',
                container: 'sessionPageViewChart',
            }
        });
        sessionPageViewChart.on("viewChanged",function(data){
            this.set(data).execute();
        });

        sessionPageViewChart.on('DateChanged',function (data) {
            let query = this.get().query;
            query['start-date'] = data['start-date'];
            query['end-date'] = query['end-date'];
            this.set(query).execute();

        });

        /* ---- End of sessionPageViewChart and its Events  -----*/


        /* ---- geoSessionChart and its Events  -----*/
        let geoSessionChart = new gapi.analytics.googleCharts.DataChart({
            reportType: 'ga',
            query:{
                'dimensions' : 'ga:country',
                'metrics'    : 'ga:sessions,ga:pageviews',
                'start-date': initialDate["start-date"],
                'end-date': initialDate["end-date"],
                'sort'       : '-ga:sessions',
            },
            chart: {
                type: 'GEO',
                container: 'geoSessionChart',
            }
        });

        geoSessionChart.on("viewChanged",function(data){
            this.set(data).execute();
        });

        geoSessionChart.on('DateChanged',function (data) {
            let query = this.get().query;
            query['start-date'] = data['start-date'];
            query['end-date'] = query['end-date'];
            this.set(query).execute();

        });

        let dataTable;
        geoSessionChart.on("success",function (response) {
            //html container of the geoChart
            const container = response.chart.container;
            //geoChart's js object
            const chart = response.chart;
            dataTable = response.dataTable;


            google.visualization.events.addListener(chart, 'ready', function (event) {
                container.addEventListener('mouseover', function (e) {
                    // dispatch click event to get hover value
                    var event = document.createEvent('SVGEvents');
                    event.initEvent('click', true, true);
                    e.target.dispatchEvent(event);
                }, false);
            });

            google.visualization.events.addListener(chart,'select', function(){
                if (!chart.getSelection().length)
                    return;
                let rowIndex = chart.getSelection()[0].row;

                selectedCountryEventRegister.forEach(function (elem) {
                    elem.emit('selectedCountryEvent',rowIndex)
                });
            });
        });

        /* ---- End of geoSessionChart and its Events  -----*/



        /* ---- userTypeChart and its Events  -----*/

        const userTypeChart = new gapi.analytics.googleCharts.DataChart({
            reportType: 'ga',
            query: {
                'dimensions': 'ga:userType',
                'metrics': 'ga:sessions',
                'start-date': initialDate["start-date"],
                'end-date': initialDate["end-date"]
            },
            chart: {
                type: 'PIE',
                options : {
                    pieHole: 0.4,
                    legend: {position: 'bottom',alignment: 'center', textStyle: {color: '#2f4459', fontSize: 16}},
                    colors: ['#2f4459','#c70219'],
                    chartArea:{width:'70%',height:'80%'}
                },
                container: 'userTypeChart',
            }
        });
        userTypeChart.on("viewChanged",function(data){
            this.set(data).execute();
        });

        userTypeChart.on('DateChanged',function (data) {
            let query = this.get().query;
            query['start-date'] = data['start-date'];
            query['end-date'] = query['end-date'];
            this.set(query).execute();

        });

        /* ---- End of userTypeChart and its Events  -----*/



        /* ---- userSessionBarChart and its Events  -----*/
        const userSessionBarChart = new gapi.analytics.report.Data({
            query:{
                'dimensions' : 'ga:country,ga:userType',
                'metrics'    : 'ga:sessions',
                'start-date': initialDate["start-date"],
                'end-date': initialDate["end-date"],
                'sort'       : 'ga:country,ga:sessions',
            }
        });
        userSessionBarChart.on("success",function (data) {
            let formattedArr = mergeCountrySessionUsertype(data);
            formattedArr = sortAndLimit(formattedArr,20);
            const dataTableArr = [['Country','New Visitor','Returning Visitor'],...formattedArr];

            let dataTable = new google.visualization.arrayToDataTable(dataTableArr);
            const options = {
                width: 800,
                bars: 'horizontal',
            };

            const chart = new google.charts.Bar(document.getElementById('userSessionChart'));
            chart.draw(dataTable, options);
        });

        userSessionBarChart.on("viewChanged",function (data) {
            this.set(data).execute();
        });

        userSessionBarChart.on('DateChanged',function (data) {
            let query = this.get().query;
            query['start-date'] = data['start-date'];
            query['end-date'] = query['end-date'];
            this.set(query).execute();

        });

        /*---- End of userSessionBarChart and its Events  -----*/




        /*---- countryDataTable and its Events  -----*/
        const cssClassNames = {
            'selectedTableRow' : 'selectedTableRow',
            'headerCell': 'headerCell',
            'hoverTableRow': 'selectedTableRow',
            'tableCell' : 'tableCell',
            'oddTableRow': 'oddTableRow'
        };
        let countryTableGlobalResponse;
        let countryDataTable = new gapi.analytics.googleCharts.DataChart({
            reportType: 'ga',
            query:{
                'dimensions' : 'ga:country',
                'metrics'    : 'ga:sessions,ga:pageviews,ga:avgSessionDuration,ga:avgTimeOnPage',
                'start-date': initialDate["start-date"],
                'end-date': initialDate["end-date"],
                'sort'       : '-ga:sessions',
                'max-results': '20'
            },
            chart: {
                type: 'TABLE',
                container: 'countryTable',
                options : {'cssClassNames': cssClassNames, 'alternatingRowStyle' : false}
            }
        });

        countryDataTable.on("viewChanged",function (data) {
            this.set(data).execute();
        });

        countryDataTable.on('DateChanged',function (data) {
            let query = this.get().query;
            query['start-date'] = data['start-date'];
            query['end-date'] = query['end-date'];
            this.set(query).execute();

        });
        countryDataTable.on("selectedCountryEvent",function (rowId) {
            if(rowId < this.get()["query"]["max-results"])
                countryTableGlobalResponse.chart.setSelection([{'row': rowId, 'column': null}]);
            else
                countryTableGlobalResponse.chart.setSelection([]);
        });

        countryDataTable.on('success',function (response) {
            countryTableGlobalResponse = response;
        });

        /*---- End of countryDataTable and its Events  -----*/


        /*---- searchEngineChart and its Events  -----*/

        let searchEngineChart = new gapi.analytics.googleCharts.DataChart({
            reportType: 'ga',
            query:{
                'dimensions' : 'ga:source',
                'metrics' : 'ga:pageviews,ga:sessionDuration,ga:exits',
                'filters' :
                    'ga:medium==cpa,ga:medium==cpc,ga:medium==cpm,ga:medium==cpp,ga:medium==cpv,ga:medium==organic,ga:medium==ppc',
                'sort' : '-ga:pageviews',
                'max-results': '5',
                'start-date': initialDate["start-date"],
                'end-date': initialDate["end-date"],
            },
            chart: {
                type: 'COLUMN',
                container: 'searchEngineChart',
                options : {
                    title: "Search Engines",
                    width: 600,
                    height: 500,
                    bar: {groupWidth: "95%"},
                    legend: { position: "none" },
                },
            }
        });

        searchEngineChart.on("viewChanged",function (data) {
            this.set(data).execute();
        });

        searchEngineChart.on('DateChanged',function (data) {
            let query = this.get().query;
            query['start-date'] = data['start-date'];
            query['end-date'] = query['end-date'];
            this.set(query).execute();

        });

        /*---- End of searchEngineChart and its Events  -----*/


        /*---- viewSelector and its Events  -----*/

        const viewSelector = new gapi.analytics.ViewSelector({
            container: 'view-selector'
        });

        /**
         * TODO:viewSelector.execute()---Should-be-removed.
         * Right way of executing selector is calling it gapi.analytics.auth.authorize method.
         * I had to use it here. When we use access_token, auth does not react methods such as on,off etc.
         * The user connect with cliend_id so it should react gapi.analytics.auth.authorize('success') method.
         * */
        viewSelector.execute();

        viewSelector.on('change', function(ids) {
            let newIds = {
                query: {
                    ids: ids
                }
            };

            viewChangeEventRegister.forEach(function (elem) {
                elem.emit("viewChanged",newIds);
            });

        });

        /*----- Event Registers ------*/
        const dateChangeEventRegister =
            [sessionPageViewChart,geoSessionChart,userSessionBarChart,countryDataTable,userTypeChart,searchEngineChart];

        const viewChangeEventRegister =
            [sessionPageViewChart,geoSessionChart,userSessionBarChart,countryDataTable,userTypeChart,searchEngineChart];

        const selectedCountryEventRegister = [countryDataTable];

        /*------  Helper functions -------*/
        /**
         * Regulates the data that comes from API
         * */
        function mergeCountrySessionUsertype(rawArray) {
            let formattedRaw = [];
            if(rawArray.rows)
                rawArray.rows.forEach(function (val,index) {
                    let country = val[0];
                    let userType = val[1];
                    let sessions = val[2];
                    let foundCountry = false;

                    //Search in second dimension. If country exist then update it
                    formattedRaw.forEach(function(val,index){
                        if(formattedRaw[index][0] === country) {
                            if (userType === "New Visitor")
                                formattedRaw[index][1] = parseInt(sessions);
                            else
                                formattedRaw[index][2] = parseInt(sessions);

                            foundCountry = true;
                            return;
                        }
                    });
                    // If the country value does not exist then insert it
                    if(foundCountry === false) {
                        let currentIndex = formattedRaw.push([country, 0, 0]) - 1;
                        if (userType === "New Visitor")
                            formattedRaw[currentIndex][1] = parseInt(sessions);
                        else
                            formattedRaw[currentIndex][2] = parseInt(sessions);
                    }
                });

            return formattedRaw;
        }

        /**
         * It sorts the array based on second index of second dimension.
         * Returns subset of the array.
         * */
        function sortAndLimit(arrToSort,limit){
            arrToSort.sort(function (a,b) {
                if (a[1] === b[1]) {
                    return 0;
                }
                else {
                    return (b[1] < a[1]) ? -1 : 1;
                }
            });
            return arrToSort.slice(0, limit - 1);
        }
    });
</script>
</body>
</html>