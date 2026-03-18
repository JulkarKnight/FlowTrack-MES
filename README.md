# FlowTrack - Intelligent Manufacturing Execution System (MES) 

FlowTrack is a dual-interface Apparel MES designed to streamline factory operations. It bridges the gap between high-level production planning and individual worker execution by transitioning shop floor management from reactive tracking to **predictive manufacturing intelligence**.

 Smart AI & Algorithmic Features (Beta)
- **Smart Assignment Engine:** Prevents worker double-booking using sub-second SQL timestamp overlap detection and enforces workload balancing limits.
- **AI Material Forecasting:** Moves beyond static Bills of Material (BOM) by calculating historical "waste factors" to predict exact raw material requirements for future batches.
- **Dynamic Workflows:** Creating a "Job Card" automatically generates all sequential production stages based on predefined templates and custom start dates.
- **Real-Time Notifications:** A custom AJAX polling API delivers live popup alerts and updates a unified notification bell instantly without page reloads.
- **Interactive Calendars:** Integrated FullCalendar.js provides workers with a personalized, visual timeline of their assigned tasks.

##  Core Operations & Management
- **Dashboard:** iOS-style animated dashboard with live charts to monitor overall factory health.
- **Batch Management:** Create, track, and complete manufacturing batches with full digital traceability.
- **Quality Control:** AQL grading system with defect tracking and automated Non-Conformance Reports (NCR).
- **Rework Bay:** Dedicated pipeline to manage defective items with strict Fix vs. Scrap logic.
- **Worker Management:** Track individual worker efficiency, performance speeds, and assignments.

##  Tech Stack
- **Frontend:** HTML5, CSS3 (Glassmorphism / iOS Design System), Vanilla JavaScript (ES6, Fetch API).
- **Libraries:** SweetAlert2 (Modals & Toasts), Chart.js (Analytics), FullCalendar.js.
- **Backend:** PHP (Native) with dedicated JSON API endpoints.
- **Database:** MySQL (3NF Normalized, Complex Time-Series Queries).

## 💻 How to Run Locally
1. Download or clone the repository.
2. Move the project folder to `xampp/htdocs` (or your preferred local server directory).
3. Open phpMyAdmin and import the `flowtrack_mes.sql` database file.
4. Open `http://localhost/FlowTrack-MES` (or your specific folder name) in your browser.
5. *Note: Ensure your local MySQL server is running on port 3306 (or update the `$port` variable in the PHP connection strings).*
