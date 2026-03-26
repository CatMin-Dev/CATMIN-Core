<!-- footer content -->
        <footer>
          <div class="float-start">
            Licence: Admin libre d'utilisations et de modification.
          </div>
          <div class="float-end">
            Basé sur <a href="https://colorlib.com" target="_blank" rel="noopener noreferrer">Gentelella de Colorlib</a> · CATMIN par <a href="https://www.letsplay.fr" target="_blank" rel="noopener noreferrer">Let's Play &amp; Friends</a>
          </div>
        </footer>
        <!-- /footer content -->

      </div>
    </div>

    <!-- Date Range Picker Logic -->
    <script type="text/javascript">
    // Note: The "Uncaught (in promise) Error: A listener indicated an asynchronous response" 
    // error is caused by browser extensions (like ad blockers) and can be safely ignored.
    // It doesn't affect the functionality of our application.
    
    document.addEventListener('DOMContentLoaded', function() {
      // Check if we have date picker elements
      const startDatePicker = document.getElementById('startDatePicker');
      const endDatePicker = document.getElementById('endDatePicker');
      
      if (startDatePicker && endDatePicker) {
        try {
          // TempusDominus is directly available in main-minimal.js
          
          const TempusDominus = window.TempusDominus || globalThis.TempusDominus;
          const DateTime = window.DateTime || globalThis.DateTime;
          
          if (TempusDominus && DateTime) {
            // Initialize the date pickers
            const startPicker = new TempusDominus(startDatePicker, {
              display: {
                components: {
                  clock: false,
                  seconds: false
                }
              },
              localization: {
                format: 'MM/dd/yyyy',
                hourCycle: 'h12'
              }
            });
            
            const endPicker = new TempusDominus(endDatePicker, {
              display: {
                components: {
                  clock: false,
                  seconds: false
                }
              },
              localization: {
                format: 'MM/dd/yyyy',
                hourCycle: 'h12'
              }
            });
            
            // Set default dates (last 30 days)
            try {
              const today = new DateTime();
              const thirtyDaysAgo = new DateTime().manipulate(-30, 'date');
              
              startPicker.dates.setValue(thirtyDaysAgo);
              endPicker.dates.setValue(today);
              
            } catch (error) {
            }
            
            // Link pickers
            startDatePicker.addEventListener('change.td', function(e) {
              endPicker.updateOptions({ restrictions: { maxDate: e.detail.date } });
            });
            
            endDatePicker.addEventListener('change.td', function(e) {
              startPicker.updateOptions({ restrictions: { minDate: e.detail.date } });
            });
            
          } else {
          }
        } catch (error) {
        }
      }
    });

    // Dashboard chart initialization - define functions first
    function initializeDashboardChart() {
      const container = document.getElementById('chart_plot_01');
      if (!container) return;
      
      // Create canvas element
      const canvas = document.createElement('canvas');
      canvas.width = container.offsetWidth || 800;
      canvas.height = 400;
      container.innerHTML = ''; // Clear any existing content
      container.appendChild(canvas);
      
      const ctx = canvas.getContext('2d');
      if (!ctx) return;

      // Sample data for the main dashboard chart
      const chartData = {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Revenue',
          data: [12, 19, 8, 15, 22, 18, 25, 32, 28, 35, 30, 40],
          borderColor: '#1ABB9C',
          backgroundColor: 'rgba(26, 187, 156, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4
        }, {
          label: 'Expenses',
          data: [8, 12, 6, 10, 15, 12, 18, 22, 20, 25, 22, 28],
          borderColor: '#E74C3C',
          backgroundColor: 'rgba(231, 76, 60, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4
        }]
      };

      const config = {
        type: 'line',
        data: chartData,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'top',
            },
            title: {
              display: true,
              text: 'Monthly Financial Overview'
            }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: {
                color: 'rgba(0,0,0,0.1)'
              }
            },
            x: {
              grid: {
                color: 'rgba(0,0,0,0.1)'
              }
            }
          }
        }
      };

      new Chart(ctx, config);
    }

    // Profile completion gauge
    function initializeProfileGauge() {
      const gaugeElement = document.getElementById('profile_completion_gauge');
      if (!gaugeElement) return;

      // Create a simple donut chart for profile completion
      const canvas = document.createElement('canvas');
      canvas.width = 160;
      canvas.height = 120;
      gaugeElement.appendChild(canvas);
      const ctx = canvas.getContext('2d');

      const data = {
        datasets: [{
          data: [67, 33],
          backgroundColor: ['#1ABB9C', '#ECF0F1'],
          borderWidth: 0
        }]
      };

      const config = {
        type: 'doughnut',
        data: data,
        options: {
          responsive: true,
          maintainAspectRatio: false,
          cutout: '75%',
          plugins: {
            legend: {
              display: false
            },
            tooltip: {
              enabled: false
            }
          }
        }
      };

      new Chart(ctx, config);
    }

    function createOrReplaceChart(chartKey, canvas, config) {
      if (!canvas || typeof window.Chart === 'undefined') return null;

      if (!window.__catminCharts) {
        window.__catminCharts = {};
      }

      if (window.__catminCharts[chartKey]) {
        window.__catminCharts[chartKey].destroy();
      }

      const context = canvas.getContext('2d');
      if (!context) return null;

      window.__catminCharts[chartKey] = new Chart(context, config);
      return window.__catminCharts[chartKey];
    }

    function initializeDailyActiveUsersChart() {
      const canvas = document.getElementById('daily_active_users_chart');
      if (!canvas) return;

      const labels = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
      const data = [182, 210, 198, 235, 268, 241, 279];

      createOrReplaceChart('dailyActiveUsers', canvas, {
        type: 'line',
        data: {
          labels,
          datasets: [{
            label: 'Daily active users',
            data,
            borderColor: '#9a1b3d',
            backgroundColor: 'rgba(154, 27, 61, 0.12)',
            borderWidth: 2,
            pointRadius: 3,
            pointHoverRadius: 5,
            fill: true,
            tension: 0.35
          }]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: { display: false }
          },
          scales: {
            y: {
              beginAtZero: true,
              grid: { color: 'rgba(0,0,0,0.08)' }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }

    function weatherCodeToLabel(code) {
      if (code === 0) return 'Ciel dégagé';
      if ([1, 2].includes(code)) return 'Partiellement nuageux';
      if (code === 3) return 'Couvert';
      if ([45, 48].includes(code)) return 'Brouillard';
      if ([51, 53, 55, 56, 57].includes(code)) return 'Bruine';
      if ([61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return 'Pluie';
      if ([71, 73, 75, 77, 85, 86].includes(code)) return 'Neige';
      if ([95, 96, 99].includes(code)) return 'Orage';
      return 'Conditions variables';
    }

    function weatherCodeToIcon(code) {
      if (code === 0) return '☀️';
      if ([1, 2].includes(code)) return '⛅';
      if (code === 3) return '☁️';
      if ([45, 48].includes(code)) return '🌫️';
      if ([51, 53, 55, 56, 57, 61, 63, 65, 66, 67, 80, 81, 82].includes(code)) return '🌧️';
      if ([71, 73, 75, 77, 85, 86].includes(code)) return '❄️';
      if ([95, 96, 99].includes(code)) return '⛈️';
      return '🌡️';
    }

    async function fetchWeatherFromBackend(latitude, longitude) {
      const query =
        typeof latitude === 'number' && typeof longitude === 'number'
          ? `?lat=${encodeURIComponent(latitude)}&lon=${encodeURIComponent(longitude)}`
          : '';

      const response = await fetch(`/rework/api/weather.php${query}`);
      if (!response.ok) {
        throw new Error('Weather backend error');
      }

      const payload = await response.json();
      if (!payload || !payload.weather) {
        throw new Error('Invalid weather payload');
      }

      return payload;
    }

    function initializeWeatherTrendChart(days, maxValues, minValues) {
      const canvas = document.getElementById('weather_temp_trend_chart');
      if (!canvas) return;

      createOrReplaceChart('weatherTrend', canvas, {
        type: 'line',
        data: {
          labels: days,
          datasets: [
            {
              label: 'Max',
              data: maxValues,
              borderColor: '#9a1b3d',
              backgroundColor: 'rgba(154, 27, 61, 0.08)',
              borderWidth: 2,
              fill: false,
              tension: 0.3
            },
            {
              label: 'Min',
              data: minValues,
              borderColor: '#6c757d',
              backgroundColor: 'rgba(108, 117, 125, 0.08)',
              borderWidth: 2,
              fill: false,
              tension: 0.3
            }
          ]
        },
        options: {
          responsive: true,
          maintainAspectRatio: false,
          plugins: {
            legend: {
              position: 'bottom',
              labels: { boxWidth: 10 }
            }
          },
          scales: {
            y: {
              ticks: {
                callback: (value) => `${value}°`
              },
              grid: { color: 'rgba(0,0,0,0.08)' }
            },
            x: {
              grid: { display: false }
            }
          }
        }
      });
    }

    function updateWeatherDom(data) {
      const current = data.current || {};
      const daily = data.daily || {};

      const currentWeatherCode = current.weather_code ?? 0;

      const weatherDescription = document.getElementById('weather_description');
      const weatherIcon = document.getElementById('weather_icon');
      const weatherTemperature = document.getElementById('weather_temperature');
      const weatherFeelsLike = document.getElementById('weather_feels_like');
      const weatherWind = document.getElementById('weather_wind');
      const weatherHumidity = document.getElementById('weather_humidity');
      const weatherUpdatedAt = document.getElementById('weather_updated_at');

      if (weatherDescription) weatherDescription.textContent = weatherCodeToLabel(currentWeatherCode);
      if (weatherIcon) weatherIcon.textContent = weatherCodeToIcon(currentWeatherCode);
      if (weatherTemperature) weatherTemperature.textContent = `${Math.round(current.temperature_2m ?? 0)}°C`;
      if (weatherFeelsLike) weatherFeelsLike.textContent = `${Math.round(current.apparent_temperature ?? 0)}°C`;
      if (weatherWind) weatherWind.textContent = `${Math.round(current.wind_speed_10m ?? 0)} km/h`;
      if (weatherHumidity) weatherHumidity.textContent = `${Math.round(current.relative_humidity_2m ?? 0)}%`;
      if (weatherUpdatedAt) {
        const now = new Date();
        weatherUpdatedAt.textContent = `Mis à jour à ${now.toLocaleTimeString('fr-FR', { hour: '2-digit', minute: '2-digit' })}`;
      }

      const days = (daily.time || []).slice(0, 7).map((item) => {
        const date = new Date(item);
        return date.toLocaleDateString('fr-FR', { weekday: 'short' });
      });
      const maxValues = (daily.temperature_2m_max || []).slice(0, 7);
      const minValues = (daily.temperature_2m_min || []).slice(0, 7);

      if (days.length && maxValues.length && minValues.length) {
        initializeWeatherTrendChart(days, maxValues, minValues);
      }
    }

    function setWeatherLocationLabel(label) {
      const weatherLocation = document.getElementById('weather_location');
      if (weatherLocation) {
        weatherLocation.textContent = label;
      }
    }

    async function initializeWeatherWidget() {
      const weatherWidget = document.getElementById('weather_widget');
      if (!weatherWidget) return;

      const weatherDescription = document.getElementById('weather_description');

      const runWeatherFlow = async (latitude, longitude, defaultLabel = 'Votre localisation') => {
        setWeatherLocationLabel(defaultLabel);
        const payload = await fetchWeatherFromBackend(latitude, longitude);
        updateWeatherDom(payload.weather);
        setWeatherLocationLabel(payload.locationLabel || defaultLabel);
      };

      const runFallbackByIp = async () => {
        await runWeatherFlow(undefined, undefined, 'Localisation approximative (IP)');
      };

      if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(
          (position) => {
            runWeatherFlow(position.coords.latitude, position.coords.longitude, 'Votre localisation')
              .catch(() => runFallbackByIp())
              .catch(() => {
                if (weatherDescription) {
                  weatherDescription.textContent = 'Météo indisponible pour le moment';
                }
                setWeatherLocationLabel('Impossible de charger la météo');
              });
          },
          () => {
            runFallbackByIp().catch(() => {
              if (weatherDescription) {
                weatherDescription.textContent = 'Météo indisponible pour le moment';
              }
              setWeatherLocationLabel('Impossible de charger la météo');
            });
          },
          { timeout: 9000, maximumAge: 300000 }
        );
      } else {
        try {
          await runFallbackByIp();
        } catch (error) {
          if (weatherDescription) {
            weatherDescription.textContent = 'Météo indisponible pour le moment';
          }
          setWeatherLocationLabel('Impossible de charger la météo');
        }
      }
    }

    // Initialize charts - Chart.js is directly available in main-minimal.js
    document.addEventListener('DOMContentLoaded', function() {
      
      // Check if basic elements exist
      const chartContainer = document.getElementById('chart_plot_01');
      const collapseLinks = document.querySelectorAll('.collapse-link');
      
      if (chartContainer) {
        // Wait for Chart.js to be available (from module)
        function waitForChart() {
          if (typeof window.Chart !== 'undefined') {
            try {
              
              // Initialize main dashboard chart
              initializeDashboardChart();
              
              // Initialize profile completion gauge
              if (document.getElementById('profile_completion_gauge')) {
                initializeProfileGauge();
              }

              // Dashboard widgets
              initializeDailyActiveUsersChart();
              initializeWeatherWidget();
              
            } catch (error) {
            }
          } else {
            // Try again in 50ms
            setTimeout(waitForChart, 50);
          }
        }
        
        // Start waiting
        waitForChart();
      } else {
      }
    });
    </script>

  </body>
</html>