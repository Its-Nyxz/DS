<div class="flex h-full w-full flex-1 flex-col gap-4 rounded-xl">
    <div class="grid auto-rows-min gap-4 md:grid-cols-3">
        <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div class="p-6 bg-white dark:bg-zinc-800 rounded-lg shadow-md">
                <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Data Master</h2>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div
                        class="bg-white dark:bg-zinc-900 rounded-lg p-4 shadow border border-gray-200 dark:border-gray-700">
                        <div id="donut-chart"></div>
                    </div>

                    <div class="grid grid-cols-2 gap-4 text-center text-gray-500 dark:text-gray-300">
                        <a href="{{ route('units.index') }}">
                            <div
                                class="bg-gray-50 dark:bg-zinc-800 p-4 rounded shadow hover:ring-2 hover:ring-blue-500 transition">
                                <p class="text-sm">Total Satuan</p>
                                <p class="text-2xl font-bold">{{ $unitCount }}</p>
                            </div>
                        </a>
                        <a href="{{ route('brands.index') }}">
                            <div
                                class="bg-gray-50 dark:bg-zinc-800 p-4 rounded shadow hover:ring-2 hover:ring-blue-500 transition">
                                <p class="text-sm">Total Merk</p>
                                <p class="text-2xl font-bold">{{ $brandCount }}</p>
                            </div>
                        </a>
                        <a href="{{ route('suppliers.index') }}">
                            <div
                                class="bg-gray-50 dark:bg-zinc-800 p-4 rounded shadow hover:ring-2 hover:ring-blue-500 transition">
                                <p class="text-sm">Total Supplier</p>
                                <p class="text-2xl font-bold">{{ $supplierCount }}</p>
                            </div>
                        </a>
                        <a href="{{ route('items.index') }}">
                            <div
                                class="bg-gray-50 dark:bg-zinc-800 p-4 rounded shadow hover:ring-2 hover:ring-blue-500 transition">
                                <p class="text-sm">Total Barang</p>
                                <p class="text-2xl font-bold">{{ $itemCount }}</p>
                            </div>
                        </a>
                    </div>

                </div>
            </div>
            @push('scripts')
                <script>
                    function renderDonutChart() {
                        const element = document.getElementById("donut-chart");

                        if (!element || typeof ApexCharts === 'undefined') {
                            console.warn("Donut chart element not found or ApexCharts is undefined.");
                            return;
                        }

                        // Destroy chart if already exists
                        if (element._apexChart) {
                            element._apexChart.destroy();
                        }

                        const options = {
                            series: [{{ $unitCount }}, {{ $brandCount }}, {{ $supplierCount }}, {{ $itemCount }}],
                            labels: ["Satuan", "Merk", "Supplier", "Barang"],
                            chart: {
                                type: 'donut',
                                height: 210,
                            },
                            legend: {
                                position: 'bottom'
                            },
                            dataLabels: {
                                enabled: false
                            },
                            plotOptions: {
                                pie: {
                                    donut: {
                                        labels: {
                                            show: true,
                                            total: {
                                                show: true,
                                                label: 'Total',
                                                formatter: function(w) {
                                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0) + ' Data';
                                                }
                                            }
                                        }
                                    }
                                }
                            },
                            theme: {
                                mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                            }
                        };

                        const chart = new ApexCharts(element, options);
                        chart.render();
                        element._apexChart = chart;
                    }

                    // Jalankan saat halaman pertama load
                    document.addEventListener("DOMContentLoaded", () => {
                        renderDonutChart();
                    });

                    // Jalankan ulang setiap navigasi SPA Livewire
                    document.addEventListener("livewire:navigated", () => {
                        setTimeout(() => {
                            renderDonutChart();
                        }, 50);
                    });
                </script>
            @endpush
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            <div
                class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
                <div class="p-6 bg-white dark:bg-zinc-800 rounded-lg shadow-md">
                    <h2 class="text-xl font-bold text-gray-800 dark:text-white mb-4">Barang per Supplier</h2>
                    <div id="supplier-item-chart"></div>
                </div>
                @push('scripts')
                    <script>
                        function renderSupplierItemChart() {
                            const el = document.getElementById("supplier-item-chart");
                            if (!el || typeof ApexCharts === 'undefined') return;

                            if (el._apexChart) {
                                el._apexChart.destroy();
                            }

                            const supplierItemData = {!! json_encode([
                                'series' => array_column($itemsPerSupplier, 'count'),
                                'labels' => array_column($itemsPerSupplier, 'name'),
                            ]) !!};

                            const options = {
                                series: supplierItemData.series,
                                labels: supplierItemData.labels,
                                chart: {
                                    height: 240,
                                    type: "donut"
                                },
                                colors: ["#6366F1", "#10B981", "#F59E0B", "#EF4444", "#8B5CF6", "#0EA5E9", "#E879F9", "#F43F5E"],
                                plotOptions: {
                                    pie: {
                                        donut: {
                                            labels: {
                                                show: false,
                                                total: {
                                                    show: true,
                                                    label: "Total",
                                                    formatter: function(w) {
                                                        const sum = w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                                        return sum + " Barang";
                                                    }
                                                }
                                            }
                                        }
                                    }
                                },
                                legend: {
                                    position: "bottom"
                                },
                                dataLabels: {
                                    enabled: false
                                },
                                theme: {
                                    mode: document.documentElement.classList.contains('dark') ? 'dark' : 'light'
                                }
                            };

                            const chart = new ApexCharts(el, options);
                            chart.render();
                            el._apexChart = chart;
                        }

                        document.addEventListener("DOMContentLoaded", renderSupplierItemChart);

                        document.addEventListener("livewire:initialized", () => {
                            Livewire.hook('message.processed', () => {
                                renderSupplierItemChart();
                            });
                        });

                        // Hindari redeklarasi observer global
                        if (!window.__supplierChartObserver__) {
                            window.__supplierChartObserver__ = new MutationObserver(renderSupplierItemChart);
                            window.__supplierChartObserver__.observe(document.documentElement, {
                                attributes: true,
                                attributeFilter: ['class']
                            });
                        }
                    </script>
                @endpush
            </div>
        </div>
        <div class="relative aspect-video overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">

            {{-- <div class="max-w-sm w-full bg-white rounded-lg shadow-sm dark:bg-gray-800 p-4 md:p-6">
                <div class="flex justify-between mb-3">
                    <div class="flex items-center">
                        <div class="flex justify-center items-center">
                            <h5 class="text-xl font-bold leading-none text-gray-900 dark:text-white pe-1">Your team's
                                progress</h5>
                            <svg data-popover-target="chart-info" data-popover-placement="bottom"
                                class="w-3.5 h-3.5 text-gray-500 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white cursor-pointer ms-1"
                                aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="currentColor"
                                viewBox="0 0 20 20">
                                <path
                                    d="M10 .5a9.5 9.5 0 1 0 9.5 9.5A9.51 9.51 0 0 0 10 .5Zm0 16a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3Zm1-5.034V12a1 1 0 0 1-2 0v-1.418a1 1 0 0 1 1.038-.999 1.436 1.436 0 0 0 1.488-1.441 1.501 1.501 0 1 0-3-.116.986.986 0 0 1-1.037.961 1 1 0 0 1-.96-1.037A3.5 3.5 0 1 1 11 11.466Z" />
                            </svg>
                            <div data-popover id="chart-info" role="tooltip"
                                class="absolute z-10 invisible inline-block text-sm text-gray-500 transition-opacity duration-300 bg-white border border-gray-200 rounded-lg shadow-xs opacity-0 w-72 dark:bg-gray-800 dark:border-gray-600 dark:text-gray-400">
                                <div class="p-3 space-y-2">
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Activity growth -
                                        Incremental</h3>
                                    <p>Report helps navigate cumulative growth of community activities. Ideally, the
                                        chart should have a growing trend, as stagnating chart signifies a significant
                                        decrease of community activity.</p>
                                    <h3 class="font-semibold text-gray-900 dark:text-white">Calculation</h3>
                                    <p>For each date bucket, the all-time volume of activities is calculated. This means
                                        that activities in period n contain all activities up to period n, plus the
                                        activities generated by your community in period.</p>
                                    <a href="#"
                                        class="flex items-center font-medium text-blue-600 dark:text-blue-500 dark:hover:text-blue-600 hover:text-blue-700 hover:underline">Read
                                        more <svg class="w-2 h-2 ms-1.5 rtl:rotate-180" aria-hidden="true"
                                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                                stroke-width="2" d="m1 9 4-4-4-4" />
                                        </svg></a>
                                </div>
                                <div data-popper-arrow></div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 dark:bg-gray-700 p-3 rounded-lg">
                    <div class="grid grid-cols-3 gap-3 mb-2">
                        <dl
                            class="bg-orange-50 dark:bg-gray-600 rounded-lg flex flex-col items-center justify-center h-[78px]">
                            <dt
                                class="w-8 h-8 rounded-full bg-orange-100 dark:bg-gray-500 text-orange-600 dark:text-orange-300 text-sm font-medium flex items-center justify-center mb-1">
                                12</dt>
                            <dd class="text-orange-600 dark:text-orange-300 text-sm font-medium">To do</dd>
                        </dl>
                        <dl
                            class="bg-teal-50 dark:bg-gray-600 rounded-lg flex flex-col items-center justify-center h-[78px]">
                            <dt
                                class="w-8 h-8 rounded-full bg-teal-100 dark:bg-gray-500 text-teal-600 dark:text-teal-300 text-sm font-medium flex items-center justify-center mb-1">
                                23</dt>
                            <dd class="text-teal-600 dark:text-teal-300 text-sm font-medium">In progress</dd>
                        </dl>
                        <dl
                            class="bg-blue-50 dark:bg-gray-600 rounded-lg flex flex-col items-center justify-center h-[78px]">
                            <dt
                                class="w-8 h-8 rounded-full bg-blue-100 dark:bg-gray-500 text-blue-600 dark:text-blue-300 text-sm font-medium flex items-center justify-center mb-1">
                                64</dt>
                            <dd class="text-blue-600 dark:text-blue-300 text-sm font-medium">Done</dd>
                        </dl>
                    </div>
                    <button data-collapse-toggle="more-details" type="button"
                        class="hover:underline text-xs text-gray-500 dark:text-gray-400 font-medium inline-flex items-center">Show
                        more details <svg class="w-2 h-2 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    <div id="more-details"
                        class="border-gray-200 border-t dark:border-gray-600 pt-3 mt-3 space-y-2 hidden">
                        <dl class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400 text-sm font-normal">Average task completion
                                rate:</dt>
                            <dd
                                class="bg-green-100 text-green-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-green-900 dark:text-green-300">
                                <svg class="w-2.5 h-2.5 me-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                    fill="none" viewBox="0 0 10 14">
                                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                        stroke-width="2" d="M5 13V1m0 0L1 5m4-4 4 4" />
                                </svg> 57%
                            </dd>
                        </dl>
                        <dl class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400 text-sm font-normal">Days until sprint ends:
                            </dt>
                            <dd
                                class="bg-gray-100 text-gray-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-gray-600 dark:text-gray-300">
                                13 days</dd>
                        </dl>
                        <dl class="flex items-center justify-between">
                            <dt class="text-gray-500 dark:text-gray-400 text-sm font-normal">Next meeting:</dt>
                            <dd
                                class="bg-gray-100 text-gray-800 text-xs font-medium inline-flex items-center px-2.5 py-1 rounded-md dark:bg-gray-600 dark:text-gray-300">
                                Thursday</dd>
                        </dl>
                    </div>
                </div>

                <!-- Radial Chart -->
                <div class="py-6" id="radial-chart"></div>

                <div
                    class="grid grid-cols-1 items-center border-gray-200 border-t dark:border-gray-700 justify-between">
                    <div class="flex justify-between items-center pt-5">
                        <!-- Button -->
                        <button id="dropdownDefaultButton" data-dropdown-toggle="lastDaysdropdown"
                            data-dropdown-placement="bottom"
                            class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 text-center inline-flex items-center dark:hover:text-white"
                            type="button">
                            Last 7 days
                            <svg class="w-2.5 m-2.5 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                                fill="none" viewBox="0 0 10 6">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 1 4 4 4-4" />
                            </svg>
                        </button>
                        <div id="lastDaysdropdown"
                            class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700">
                            <ul class="py-2 text-sm text-gray-700 dark:text-gray-200"
                                aria-labelledby="dropdownDefaultButton">
                                <li>
                                    <a href="#"
                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Today</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                        7 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                        30 days</a>
                                </li>
                                <li>
                                    <a href="#"
                                        class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                        90 days</a>
                                </li>
                            </ul>
                        </div>
                        <a href="#"
                            class="uppercase text-sm font-semibold inline-flex items-center rounded-lg text-blue-600 hover:text-blue-700 dark:hover:text-blue-500  hover:bg-gray-100 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 px-3 py-2">
                            Progress report
                            <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" aria-hidden="true"
                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                                <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                    stroke-width="2" d="m1 9 4-4-4-4" />
                            </svg>
                        </a>
                    </div>
                </div>
            </div> --}}
            {{-- @push('scripts')
                <script>
                    const getChartOptions = () => {
                        return {
                            series: [90, 85, 70],
                            colors: ["#1C64F2", "#16BDCA", "#FDBA8C"],
                            chart: {
                                height: "350px",
                                width: "100%",
                                type: "radialBar",
                                sparkline: {
                                    enabled: true,
                                },
                            },
                            plotOptions: {
                                radialBar: {
                                    track: {
                                        background: '#E5E7EB',
                                    },
                                    dataLabels: {
                                        show: false,
                                    },
                                    hollow: {
                                        margin: 0,
                                        size: "32%",
                                    }
                                },
                            },
                            grid: {
                                show: false,
                                strokeDashArray: 4,
                                padding: {
                                    left: 2,
                                    right: 2,
                                    top: -23,
                                    bottom: -20,
                                },
                            },
                            labels: ["Done", "In progress", "To do"],
                            legend: {
                                show: true,
                                position: "bottom",
                                fontFamily: "Inter, sans-serif",
                            },
                            tooltip: {
                                enabled: true,
                                x: {
                                    show: false,
                                },
                            },
                            yaxis: {
                                show: false,
                                labels: {
                                    formatter: function(value) {
                                        return value + '%';
                                    }
                                }
                            }
                        }
                    }

                    if (document.getElementById("radial-chart") && typeof ApexCharts !== 'undefined') {
                        const chart = new ApexCharts(document.querySelector("#radial-chart"), getChartOptions());
                        chart.render();
                    }
                </script>
            @endpush --}}
        </div>
    </div>
    <div class="relative h-full flex-1 overflow-hidden rounded-xl border border-neutral-200 dark:border-neutral-700">
        {{-- <div class="max-w-sm w-full bg-white rounded-lg shadow-sm dark:bg-gray-800 p-4 md:p-6">
            <div class="flex justify-between">
                <div>
                    <h5 class="leading-none text-3xl font-bold text-gray-900 dark:text-white pb-2">$12,423</h5>
                    <p class="text-base font-normal text-gray-500 dark:text-gray-400">Sales this week</p>
                </div>
                <div
                    class="flex items-center px-2.5 py-0.5 text-base font-semibold text-green-500 dark:text-green-500 text-center">
                    23%
                    <svg class="w-3 h-3 ms-1" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none"
                        viewBox="0 0 10 14">
                        <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M5 13V1m0 0L1 5m4-4 4 4" />
                    </svg>
                </div>
            </div>
            <div id="data-series-chart"></div>
            <div
                class="grid grid-cols-1 items-center border-gray-200 border-t dark:border-gray-700 justify-between mt-5">
                <div class="flex justify-between items-center pt-5">
                    <!-- Button -->
                    <button id="dropdownDefaultButton" data-dropdown-toggle="lastDaysdropdown"
                        data-dropdown-placement="bottom"
                        class="text-sm font-medium text-gray-500 dark:text-gray-400 hover:text-gray-900 text-center inline-flex items-center dark:hover:text-white"
                        type="button">
                        Last 7 days
                        <svg class="w-2.5 m-2.5 ms-1.5" aria-hidden="true" xmlns="http://www.w3.org/2000/svg"
                            fill="none" viewBox="0 0 10 6">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m1 1 4 4 4-4" />
                        </svg>
                    </button>
                    <!-- Dropdown menu -->
                    <div id="lastDaysdropdown"
                        class="z-10 hidden bg-white divide-y divide-gray-100 rounded-lg shadow-sm w-44 dark:bg-gray-700">
                        <ul class="py-2 text-sm text-gray-700 dark:text-gray-200"
                            aria-labelledby="dropdownDefaultButton">
                            <li>
                                <a href="#"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Yesterday</a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Today</a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                    7 days</a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                    30 days</a>
                            </li>
                            <li>
                                <a href="#"
                                    class="block px-4 py-2 hover:bg-gray-100 dark:hover:bg-gray-600 dark:hover:text-white">Last
                                    90 days</a>
                            </li>
                        </ul>
                    </div>
                    <a href="#"
                        class="uppercase text-sm font-semibold inline-flex items-center rounded-lg text-blue-600 hover:text-blue-700 dark:hover:text-blue-500  hover:bg-gray-100 dark:hover:bg-gray-700 dark:focus:ring-gray-700 dark:border-gray-700 px-3 py-2">
                        Sales Report
                        <svg class="w-2.5 h-2.5 ms-1.5 rtl:rotate-180" aria-hidden="true"
                            xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
                            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round"
                                stroke-width="2" d="m1 9 4-4-4-4" />
                        </svg>
                    </a>
                </div>
            </div>
        </div> --}}

        {{-- @push('scripts')
            <script type="module">
                const options = {
                    // add data series via arrays, learn more here: https://apexcharts.com/docs/series/
                    series: [{
                            name: "Developer Edition",
                            data: [1500, 1418, 1456, 1526, 1356, 1256],
                            color: "#1A56DB",
                        },
                        {
                            name: "Designer Edition",
                            data: [643, 413, 765, 412, 1423, 1731],
                            color: "#7E3BF2",
                        },
                    ],
                    chart: {
                        height: "100%",
                        maxWidth: "100%",
                        type: "area",
                        fontFamily: "Inter, sans-serif",
                        dropShadow: {
                            enabled: false,
                        },
                        toolbar: {
                            show: false,
                        },
                    },
                    tooltip: {
                        enabled: true,
                        x: {
                            show: false,
                        },
                    },
                    legend: {
                        show: false
                    },
                    fill: {
                        type: "gradient",
                        gradient: {
                            opacityFrom: 0.55,
                            opacityTo: 0,
                            shade: "#1C64F2",
                            gradientToColors: ["#1C64F2"],
                        },
                    },
                    dataLabels: {
                        enabled: false,
                    },
                    stroke: {
                        width: 6,
                    },
                    grid: {
                        show: false,
                        strokeDashArray: 4,
                        padding: {
                            left: 2,
                            right: 2,
                            top: 0
                        },
                    },
                    xaxis: {
                        categories: ['01 February', '02 February', '03 February', '04 February', '05 February', '06 February',
                            '07 February'
                        ],
                        labels: {
                            show: false,
                        },
                        axisBorder: {
                            show: false,
                        },
                        axisTicks: {
                            show: false,
                        },
                    },
                    yaxis: {
                        show: false,
                        labels: {
                            formatter: function(value) {
                                return '$' + value;
                            }
                        }
                    },
                }

                if (document.getElementById("data-series-chart") && typeof ApexCharts !== 'undefined') {
                    const chart = new ApexCharts(document.getElementById("data-series-chart"), options);
                    chart.render();
                }
            </script>
        @endpush --}}
    </div>
</div>
