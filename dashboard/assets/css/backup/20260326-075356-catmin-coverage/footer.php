<!-- footer content -->
        <footer>
          <div class="float-end">
            Gentelella - Bootstrap Admin Template by <a href="https://colorlib.com">Colorlib</a>
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