(function($) {
    'use strict';
    
    console.log('WP Link Tracker Admin JS loaded');
    
    // Chart instances for cleanup
    var chartInstances = {
        clicks: null,
        devices: null,
        browsers: null,
        os: null
    };
    
    // Initialize dashboard
    if ($('#wplinktracker-total-clicks').length) {
        loadDashboardData();
        loadTopLinks();
        loadTopReferrers();
        loadClicksOverTime();
        loadDeviceCharts(); // Add device charts loading
        loadBrowserCharts(); // Add browser charts loading
        loadOSCharts(); // Add OS charts loading
    }
    
    // Date range change handler
    $('#wplinktracker-date-range-select').on('change', function() {
        var selectedValue = $(this).val();
        
        if (selectedValue === 'custom') {
            $('#wplinktracker-custom-date-range').show();
        } else {
            $('#wplinktracker-custom-date-range').hide();
            refreshAllData();
        }
    });
    
    // Apply custom date range
    $('#wplinktracker-apply-date-range').on('click', function() {
        refreshAllData();
    });
    
    // Refresh dashboard
    $('#wplinktracker-refresh-dashboard, #wplinktracker-refresh-data').on('click', function() {
        refreshAllData();
    });
    
    // Refresh all data function
    function refreshAllData() {
        loadDashboardData();
        loadTopLinks();
        loadTopReferrers();
        loadClicksOverTime();
        loadDeviceCharts();
        loadBrowserCharts();
        loadOSCharts();
    }
    
    // Debug date range
    $('#wplinktracker-debug-date-range').on('click', function() {
        debugDateRange();
    });
    
    // View data count
    $('#wplinktracker-view-data-count').on('click', function() {
        viewDataCount();
    });
    
    // Reset statistics
    $('#wplinktracker-reset-stats').on('click', function() {
        if (confirm(wpLinkTrackerAdmin.confirmResetStats)) {
            resetStatistics();
        }
    });
    
    // Copy to clipboard functionality
    $(document).on('click', '.copy-to-clipboard', function() {
        var text = $(this).data('clipboard-text');
        
        // Create temporary textarea
        var $temp = $('<textarea>');
        $('body').append($temp);
        $temp.val(text).select();
        document.execCommand('copy');
        $temp.remove();
        
        // Show feedback
        var $button = $(this);
        var originalText = $button.text();
        $button.text('Copied!');
        setTimeout(function() {
            $button.text(originalText);
        }, 2000);
    });
    
    /**
     * Load dashboard data
     */
    function loadDashboardData() {
        console.log('Loading dashboard data...');
        
        var dateRange = getDateRange();
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_dashboard_stats',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Dashboard data response:', response);
            
            if (response.success) {
                updateDashboardSummary(response.data);
            } else {
                console.error('Failed to load dashboard data:', response.data);
                showError('Failed to load dashboard data');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading dashboard data:', error);
            showError('Network error loading dashboard data');
        });
    }
    
    /**
     * Load top performing links
     */
    function loadTopLinks() {
        console.log('Loading top links...');
        
        var dateRange = getDateRange();
        
        // Show loading state
        $('#wplinktracker-top-links-table').html(
            '<div class="wplinktracker-loading">' +
            '<span class="spinner is-active" style="float: none; margin: 0;"></span>' +
            '<span>Loading...</span>' +
            '</div>'
        );
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_top_links',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Top links response:', response);
            
            if (response.success) {
                displayTopLinks(response.data);
            } else {
                console.error('Failed to load top links:', response.data);
                $('#wplinktracker-top-links-table').html('<p>Failed to load top links data.</p>');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading top links:', error);
            $('#wplinktracker-top-links-table').html('<p>Network error loading top links data.</p>');
        });
    }
    
    /**
     * Load top referrers
     */
    function loadTopReferrers() {
        console.log('Loading top referrers...');
        
        var dateRange = getDateRange();
        
        // Show loading state
        $('#wplinktracker-top-referrers-table').html(
            '<div class="wplinktracker-loading">' +
            '<span class="spinner is-active" style="float: none; margin: 0;"></span>' +
            '<span>Loading...</span>' +
            '</div>'
        );
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_top_referrers',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Top referrers response:', response);
            
            if (response.success) {
                displayTopReferrers(response.data);
            } else {
                console.error('Failed to load top referrers:', response.data);
                $('#wplinktracker-top-referrers-table').html('<p>Failed to load top referrers data.</p>');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading top referrers:', error);
            $('#wplinktracker-top-referrers-table').html('<p>Network error loading top referrers data.</p>');
        });
    }
    
    /**
     * Load clicks over time chart
     */
    function loadClicksOverTime() {
        console.log('Loading clicks over time...');
        
        var dateRange = getDateRange();
        
        // Show loading state
        $('#wplinktracker-chart-loading').show();
        $('#wplinktracker-chart-container').hide();
        
        // Destroy existing chart if it exists
        if (chartInstances.clicks) {
            chartInstances.clicks.destroy();
            chartInstances.clicks = null;
        }
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_clicks_over_time',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Clicks over time response:', response);
            
            if (response.success) {
                displayClicksOverTimeChart(response.data);
            } else {
                console.error('Failed to load clicks over time:', response.data);
                showChartError('#wplinktracker-chart-loading', '#wplinktracker-chart-container', 'Failed to load clicks over time data.');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading clicks over time:', error);
            showChartError('#wplinktracker-chart-loading', '#wplinktracker-chart-container', 'Network error loading clicks over time data.');
        });
    }
    
    /**
     * Load device charts
     */
    function loadDeviceCharts() {
        console.log('Loading device charts...');
        
        var dateRange = getDateRange();
        
        // Show loading state
        $('#wplinktracker-devices-loading').show();
        $('#wplinktracker-devices-container').hide();
        
        // Destroy existing chart if it exists
        if (chartInstances.devices) {
            chartInstances.devices.destroy();
            chartInstances.devices = null;
        }
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_device_stats',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Device stats response:', response);
            
            if (response.success) {
                displayDeviceChart(response.data);
            } else {
                console.error('Failed to load device stats:', response.data);
                showChartError('#wplinktracker-devices-loading', '#wplinktracker-devices-container', 'Failed to load device statistics.');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading device stats:', error);
            showChartError('#wplinktracker-devices-loading', '#wplinktracker-devices-container', 'Network error loading device statistics.');
        });
    }
    
    /**
     * Load browser charts
     */
    function loadBrowserCharts() {
        console.log('Loading browser charts...');
        
        var dateRange = getDateRange();
        
        // Show loading state
        $('#wplinktracker-browsers-loading').show();
        $('#wplinktracker-browsers-container').hide();
        
        // Destroy existing chart if it exists
        if (chartInstances.browsers) {
            chartInstances.browsers.destroy();
            chartInstances.browsers = null;
        }
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_browser_stats',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Browser stats response:', response);
            
            if (response.success) {
                displayBrowserChart(response.data);
            } else {
                console.error('Failed to load browser stats:', response.data);
                showChartError('#wplinktracker-browsers-loading', '#wplinktracker-browsers-container', 'Failed to load browser statistics.');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading browser stats:', error);
            showChartError('#wplinktracker-browsers-loading', '#wplinktracker-browsers-container', 'Network error loading browser statistics.');
        });
    }
    
    /**
     * Load OS charts
     */
    function loadOSCharts() {
        console.log('Loading OS charts...');
        
        var dateRange = getDateRange();
        
        // Show loading state
        $('#wplinktracker-os-loading').show();
        $('#wplinktracker-os-container').hide();
        
        // Destroy existing chart if it exists
        if (chartInstances.os) {
            chartInstances.os.destroy();
            chartInstances.os = null;
        }
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_get_os_stats',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('OS stats response:', response);
            
            if (response.success) {
                displayOSChart(response.data);
            } else {
                console.error('Failed to load OS stats:', response.data);
                showChartError('#wplinktracker-os-loading', '#wplinktracker-os-container', 'Failed to load OS statistics.');
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error loading OS stats:', error);
            showChartError('#wplinktracker-os-loading', '#wplinktracker-os-container', 'Network error loading OS statistics.');
        });
    }
    
    /**
     * Display clicks over time chart
     */
    function displayClicksOverTimeChart(data) {
        console.log('Displaying clicks over time chart with data:', data);
        
        // Hide loading state and show chart container
        $('#wplinktracker-chart-loading').hide();
        $('#wplinktracker-chart-container').show();
        
        var ctx = document.getElementById('wplinktracker-clicks-chart');
        if (!ctx) {
            console.error('Chart canvas not found');
            return;
        }
        
        // Prepare data for Chart.js
        var labels = [];
        var clicksData = [];
        
        if (data && data.length > 0) {
            data.forEach(function(item) {
                labels.push(item.formatted_date);
                clicksData.push(item.clicks);
            });
        } else {
            // Show empty state
            labels = ['No Data'];
            clicksData = [0];
        }
        
        // Create the chart
        chartInstances.clicks = new Chart(ctx, {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: wpLinkTrackerAdmin.chartLabels.clicks || 'Clicks',
                    data: clicksData,
                    backgroundColor: 'rgba(0, 115, 170, 0.1)',
                    borderColor: 'rgba(0, 115, 170, 1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: 'rgba(0, 115, 170, 1)',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(0, 115, 170, 1)',
                        borderWidth: 1,
                        cornerRadius: 4,
                        displayColors: false,
                        callbacks: {
                            title: function(context) {
                                return context[0].label;
                            },
                            label: function(context) {
                                var clicks = context.parsed.y;
                                return clicks + (clicks === 1 ? ' click' : ' clicks');
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        grid: {
                            display: false
                        },
                        ticks: {
                            maxTicksLimit: 10,
                            color: '#666'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        grid: {
                            color: 'rgba(0, 0, 0, 0.1)'
                        },
                        ticks: {
                            stepSize: 1,
                            color: '#666',
                            callback: function(value) {
                                return Math.floor(value) === value ? value : '';
                            }
                        }
                    }
                },
                elements: {
                    point: {
                        hoverBackgroundColor: 'rgba(0, 115, 170, 1)'
                    }
                }
            }
        });
        
        console.log('Clicks chart created successfully');
    }
    
    /**
     * Display device chart
     */
    function displayDeviceChart(data) {
        console.log('Displaying device chart with data:', data);
        
        // Hide loading state and show chart container
        $('#wplinktracker-devices-loading').hide();
        $('#wplinktracker-devices-container').show();
        
        var ctx = document.getElementById('wplinktracker-devices-chart');
        if (!ctx) {
            console.error('Device chart canvas not found');
            return;
        }
        
        // Prepare data for Chart.js
        var labels = [];
        var chartData = [];
        var colors = ['#0073aa', '#00a32a', '#d63638', '#ff8c00', '#8e44ad'];
        
        if (data && data.length > 0) {
            data.forEach(function(item, index) {
                labels.push(item.device_type);
                chartData.push(item.clicks);
            });
        } else {
            labels = ['No Data'];
            chartData = [1];
        }
        
        // Create the chart
        chartInstances.devices = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: chartData,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(0, 115, 170, 1)',
                        borderWidth: 1,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('Device chart created successfully');
    }
    
    /**
     * Display browser chart
     */
    function displayBrowserChart(data) {
        console.log('Displaying browser chart with data:', data);
        
        // Hide loading state and show chart container
        $('#wplinktracker-browsers-loading').hide();
        $('#wplinktracker-browsers-container').show();
        
        var ctx = document.getElementById('wplinktracker-browsers-chart');
        if (!ctx) {
            console.error('Browser chart canvas not found');
            return;
        }
        
        // Prepare data for Chart.js
        var labels = [];
        var chartData = [];
        var colors = ['#0073aa', '#00a32a', '#d63638', '#ff8c00', '#8e44ad', '#e67e22', '#1abc9c', '#34495e'];
        
        if (data && data.length > 0) {
            data.forEach(function(item, index) {
                labels.push(item.browser);
                chartData.push(item.clicks);
            });
        } else {
            labels = ['No Data'];
            chartData = [1];
        }
        
        // Create the chart
        chartInstances.browsers = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: chartData,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(0, 115, 170, 1)',
                        borderWidth: 1,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('Browser chart created successfully');
    }
    
    /**
     * Display OS chart
     */
    function displayOSChart(data) {
        console.log('Displaying OS chart with data:', data);
        
        // Hide loading state and show chart container
        $('#wplinktracker-os-loading').hide();
        $('#wplinktracker-os-container').show();
        
        var ctx = document.getElementById('wplinktracker-os-chart');
        if (!ctx) {
            console.error('OS chart canvas not found');
            return;
        }
        
        // Prepare data for Chart.js
        var labels = [];
        var chartData = [];
        var colors = ['#0073aa', '#00a32a', '#d63638', '#ff8c00', '#8e44ad', '#e67e22', '#1abc9c', '#34495e'];
        
        if (data && data.length > 0) {
            data.forEach(function(item, index) {
                labels.push(item.os);
                chartData.push(item.clicks);
            });
        } else {
            labels = ['No Data'];
            chartData = [1];
        }
        
        // Create the chart
        chartInstances.os = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: chartData,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 20,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(0, 0, 0, 0.8)',
                        titleColor: '#fff',
                        bodyColor: '#fff',
                        borderColor: 'rgba(0, 115, 170, 1)',
                        borderWidth: 1,
                        cornerRadius: 4,
                        callbacks: {
                            label: function(context) {
                                var total = context.dataset.data.reduce(function(a, b) { return a + b; }, 0);
                                var percentage = ((context.parsed / total) * 100).toFixed(1);
                                return context.label + ': ' + context.parsed + ' (' + percentage + '%)';
                            }
                        }
                    }
                }
            }
        });
        
        console.log('OS chart created successfully');
    }
    
    /**
     * Show chart error
     */
    function showChartError(loadingSelector, containerSelector, message) {
        $(loadingSelector).hide();
        $(containerSelector).show().html(
            '<div style="text-align: center; padding: 50px; color: #666;">' +
            '<p>' + message + '</p>' +
            '</div>'
        );
    }
    
    /**
     * Display top performing links
     */
    function displayTopLinks(links) {
        var html = '';
        
        if (links.length === 0) {
            html = '<p>' + wpLinkTrackerAdmin.noDataMessage + '</p>';
        } else {
            html = '<table class="widefat striped">';
            html += '<thead><tr>';
            html += '<th>Link Title</th>';
            html += '<th>Short URL</th>';
            html += '<th>Clicks</th>';
            html += '<th>Unique Visitors</th>';
            html += '<th>Conversion Rate</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            
            $.each(links, function(index, link) {
                html += '<tr>';
                html += '<td><strong>' + escapeHtml(link.title) + '</strong>';
                if (link.destination_url) {
                    html += '<br><small><a href="' + escapeHtml(link.destination_url) + '" target="_blank">' + escapeHtml(link.destination_url) + '</a></small>';
                }
                html += '</td>';
                html += '<td>';
                if (link.short_url) {
                    html += '<a href="' + escapeHtml(link.short_url) + '" target="_blank">' + escapeHtml(link.short_url) + '</a>';
                    html += '<br><button type="button" class="button button-small copy-to-clipboard" data-clipboard-text="' + escapeHtml(link.short_url) + '">Copy</button>';
                } else {
                    html += '—';
                }
                html += '</td>';
                html += '<td>' + link.total_clicks + '</td>';
                html += '<td>' + link.unique_visitors + '</td>';
                html += '<td>' + link.conversion_rate + '%</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        $('#wplinktracker-top-links-table').html(html);
    }
    
    /**
     * Display top referrers
     */
    function displayTopReferrers(referrers) {
        var html = '';
        
        if (referrers.length === 0) {
            html = '<p>No referrer data available yet.</p>';
        } else {
            html = '<table class="widefat striped">';
            html += '<thead><tr>';
            html += '<th>Referrer</th>';
            html += '<th>Clicks</th>';
            html += '</tr></thead>';
            html += '<tbody>';
            
            $.each(referrers, function(index, referrer) {
                html += '<tr>';
                html += '<td>';
                if (referrer.domain && referrer.domain !== referrer.referrer) {
                    html += '<strong>' + escapeHtml(referrer.domain) + '</strong>';
                    html += '<br><small><a href="' + escapeHtml(referrer.referrer) + '" target="_blank">' + escapeHtml(referrer.referrer) + '</a></small>';
                } else {
                    html += '<a href="' + escapeHtml(referrer.referrer) + '" target="_blank">' + escapeHtml(referrer.referrer) + '</a>';
                }
                html += '</td>';
                html += '<td>' + referrer.clicks + '</td>';
                html += '</tr>';
            });
            
            html += '</tbody></table>';
        }
        
        $('#wplinktracker-top-referrers-table').html(html);
    }
    
    /**
     * Update dashboard summary
     */
    function updateDashboardSummary(data) {
        $('#wplinktracker-total-clicks').text(data.total_clicks);
        $('#wplinktracker-unique-visitors').text(data.unique_visitors);
        $('#wplinktracker-active-links').text(data.active_links);
        $('#wplinktracker-avg-conversion').text(data.avg_conversion);
    }
    
    /**
     * Get current date range settings
     */
    function getDateRange() {
        var selectedRange = $('#wplinktracker-date-range-select').val();
        
        if (selectedRange === 'custom') {
            return {
                days: 30,
                date_from: $('#wplinktracker-date-from').val(),
                date_to: $('#wplinktracker-date-to').val()
            };
        } else {
            return {
                days: parseInt(selectedRange),
                date_from: '',
                date_to: ''
            };
        }
    }
    
    /**
     * Debug date range
     */
    function debugDateRange() {
        var dateRange = getDateRange();
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_debug_date_range',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Debug date range response:', response);
            alert('Debug information logged to console. Check browser developer tools.');
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error in debug date range:', error);
        });
    }
    
    /**
     * View data count
     */
    function viewDataCount() {
        var dateRange = getDateRange();
        
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_view_data_count',
            nonce: wpLinkTrackerAdmin.nonce,
            days: dateRange.days,
            date_from: dateRange.date_from,
            date_to: dateRange.date_to
        })
        .done(function(response) {
            console.log('Data count response:', response);
            if (response.success) {
                var data = response.data;
                var message = 'Data Count Information:\n\n';
                message += 'Tracked Links: ' + data.tracked_links + '\n';
                message += 'Clicks Table Exists: ' + (data.clicks_table_exists ? 'Yes' : 'No') + '\n';
                message += 'Total Click Records: ' + data.total_click_records + '\n';
                message += 'Filtered Click Records: ' + data.filtered_click_records + '\n';
                message += '\nDate Range: ' + data.date_range.days + ' days';
                if (data.date_range.date_from && data.date_range.date_to) {
                    message += ' (Custom: ' + data.date_range.date_from + ' to ' + data.date_range.date_to + ')';
                }
                
                alert(message);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error in view data count:', error);
        });
    }
    
    /**
     * Reset statistics
     */
    function resetStatistics() {
        $.post(wpLinkTrackerAdmin.ajaxUrl, {
            action: 'wp_link_tracker_reset_stats',
            nonce: wpLinkTrackerAdmin.nonce
        })
        .done(function(response) {
            console.log('Reset stats response:', response);
            
            if (response.success) {
                alert(wpLinkTrackerAdmin.resetStatsSuccess);
                // Reload dashboard data
                refreshAllData();
            } else {
                alert(wpLinkTrackerAdmin.resetStatsError + ' ' + response.data);
            }
        })
        .fail(function(xhr, status, error) {
            console.error('AJAX error resetting stats:', error);
            alert(wpLinkTrackerAdmin.resetStatsError);
        });
    }
    
    /**
     * Show error message
     */
    function showError(message) {
        console.error('Error:', message);
        // You could implement a more sophisticated error display here
    }
    
    /**
     * Escape HTML to prevent XSS
     */
    function escapeHtml(text) {
        var map = {
            '&': '&amp;',
            '<': '<',
            '>': '>',
            '"': '&quot;',
            "'": '&#039;'
        };
        
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Clean up charts when page is about to be hidden/unloaded
    function cleanupCharts() {
        Object.keys(chartInstances).forEach(function(key) {
            if (chartInstances[key]) {
                try {
                    chartInstances[key].destroy();
                } catch (e) {
                    console.warn('Error destroying chart:', e);
                }
                chartInstances[key] = null;
            }
        });
    }

    // Use modern page lifecycle events instead of deprecated unload
    document.addEventListener('visibilitychange', function() {
        if (document.visibilityState === 'hidden') {
            cleanupCharts();
        }
    });

    // Fallback for older browsers
    window.addEventListener('pagehide', function() {
        cleanupCharts();
    });

    // Also clean up on beforeunload as a final fallback
    window.addEventListener('beforeunload', function() {
        cleanupCharts();
    });

})(jQuery);
