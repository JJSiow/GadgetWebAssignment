$(() => {
    if (typeof CanvasJS === "undefined") {
        console.error("CanvasJS is not loaded.");
        return;
    }

    $.ajax({
        url: 'fetch_chart_data.php',
        method: 'GET',
        dataType: 'json',
        success: function (dataPoints) {
            var options = {
                animationEnabled: true,
                title: {
                    text: "Top 5 Best-Selling Gadgets"
                },
                axisY: {
                    title: "Percentage of Total Sales",
                    suffix: "%"
                },
                axisX: {
                    title: "Gadgets"
                },
                data: [{
                    type: "column",
                    yValueFormatString: "#,##0.00#",
                    toolTipContent: "<b>{label}</b><br>Percentage: {y}%<br>Quantity Sold: {quantitySold}",
                    dataPoints: dataPoints
                }]
            };
            $("#barChartContainer").CanvasJSChart(options);
        },
        error: function (xhr, status, error) {
            console.error("Error fetching data:", error);
        }
    });

    // Pie Chart Labels
    $.ajax({
        url: 'fetch_customer_data.php',
        method: 'GET',
        dataType: 'json',
        success: function (data) {
            var totalCustomers = data.summary.new_customers + data.summary.returning_customers;

            var visitorsData = {
                "New vs Returning Customers": [{
                    click: visitorsChartDrilldownHandler,
                    cursor: "pointer",
                    explodeOnClick: false,
                    innerRadius: "75%",
                    legendMarkerType: "square",
                    name: "New vs Returning Customers",
                    radius: "100%",
                    showInLegend: true,
                    startAngle: 90,
                    type: "doughnut",
                    dataPoints: [
                        { y: data.summary.new_customers, name: "New Customers", color: "#E7823A" },
                        { y: data.summary.returning_customers, name: "Returning Customers", color: "#546BC1" }
                    ]
                }],
                "New Customers": [{
                    color: "#E7823A",
                    name: "New Customers",
                    type: "column",
                    xValueFormatString: "MMM YYYY",
                    dataPoints: data.new_customers.map(function (item) {
                        return { x: new Date(item.month), y: item.new_customers };
                    })
                }],
                "Returning Customers": [{
                    color: "#546BC1",
                    name: "Returning Customers",
                    type: "column",
                    xValueFormatString: "MMM YYYY",
                    dataPoints: data.returning_customers.map(function (item) {
                        return { x: new Date(item.month), y: item.returning_customers };
                    })
                }]
            };

            var newVSReturningVisitorsOptions = {
                animationEnabled: true,
                theme: "light2",
                title: {
                    text: "New VS Returning Customers"
                },
                subtitles: [{
                    text: "Click on Any Segment to Drilldown",
                    backgroundColor: "#2eacd1",
                    fontSize: 16,
                    fontColor: "white",
                    padding: 5
                }],
                legend: {
                    fontFamily: "calibri",
                    fontSize: 14,
                    itemTextFormatter: function (e) {
                        return e.dataPoint.name + ": " + Math.round(e.dataPoint.y / totalCustomers * 100) + "%";
                    }
                },
                data: visitorsData["New vs Returning Customers"]
            };

            var visitorsDrilldownedChartOptions = {
                animationEnabled: true,
                theme: "light2",
                axisX: {
                    labelFontColor: "#717171",
                    lineColor: "#a2a2a2",
                    tickColor: "#a2a2a2"
                },
                axisY: {
                    gridThickness: 0,
                    includeZero: false,
                    labelFontColor: "#717171",
                    lineColor: "#a2a2a2",
                    tickColor: "#a2a2a2",
                    lineThickness: 1
                },
                data: []
            };

            $("#doughnutChartContainer").CanvasJSChart(newVSReturningVisitorsOptions);

            function visitorsChartDrilldownHandler(e) {
                e.chart.options = visitorsDrilldownedChartOptions;
                e.chart.options.data = visitorsData[e.dataPoint.name];
                e.chart.options.title = { text: e.dataPoint.name }
                e.chart.render();
                $("#backButton").toggleClass("invisible");
            }

            $("#backButton").click(function () {
                $(this).toggleClass("invisible");
                newVSReturningVisitorsOptions.data = visitorsData["New vs Returning Customers"];
                $("#doughnutChartContainer").CanvasJSChart(newVSReturningVisitorsOptions);
            });
        },
        error: function (err) {
            console.error("Error fetching data:", err);
        }
    });
});

