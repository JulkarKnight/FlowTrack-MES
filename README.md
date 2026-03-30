# FlowTrack - Intelligent Manufacturing Execution System (MES) 


<img width="1920" height="1080" alt="Screenshot (179)" src="https://github.com/user-attachments/assets/2b5c432f-c355-49e9-9191-90ea32187093" />
<img width="1920" height="1080" alt="Screenshot (178)" src="https://github.com/user-attachments/assets/30e8276c-9359-41da-954c-1e72b2ba421b" />
<img width="1919" height="1079" alt="Screenshot 2026-03-22 143110" src="https://github.com/user-attachments/assets/a3393ceb-dbc0-4587-ad8d-ac83c6654215" />
<img width="1920" height="1080" alt="Screenshot (172)" src="https://github.com/user-attachments/assets/f763cdf7-03b5-4040-8883-cfe938843eca" />
<img width="1920" height="1080" alt="Screenshot (176)" src="https://github.com/user-attachments/assets/099b1384-e9f6-4995-9555-6d6e8e531f3a" />
<img width="1920" height="1080" alt="Screenshot (173)" src="https://github.com/user-attachments/assets/b36e0d84-8bf5-43af-a858-cd261c61025e" />

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
