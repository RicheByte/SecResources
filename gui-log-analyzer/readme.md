

# 🚀 GUI Log Analyzer 

A fast, simple desktop app to detect suspicious activities (SQL Injection, XSS, Path Traversal, etc.) from web server logs — no setup hassle, just run and detect 🚀

Built with **Tkinter** + **Pandas** + **Python threading**.


---

## 📦 Installation

1. Clone the repository:
    

```bash
git clone https://github.com/richebyte/SecResources
cd gui-log-analyzer
```

2. Install the required package:
    

```bash
pip install pandas
```

---

## 🖥️ How to Use

1. Run the app:
    

```bash
python gui_log_analyzer.py
```

2. Click **"Select Log File"** and pick your `.log` or `.txt` file.
    
3. Choose the output format:
    
    - **JSON**
        
    - **CSV**
        
4. (Optional) Set the **Brute-force Threshold** (default: `100` requests from one IP = suspicious).
    
5. Click **"Analyze"** to start scanning.
    
6. Watch the **progress bar** move!
    
7. When done, the results will be saved inside a folder called `gui_log_results/` with a timestamped filename.
    

---

## 🚨 What it Detects

|Attack Type|Description|
|---|---|
|SQL Injection|Common SQL payloads, suspicious patterns|
|XSS|Cross-site scripting attempts|
|Path Traversal|Directory traversal attacks|
|Scanner Detection|Automated attack tools (sqlmap, nikto, wpscan, etc.)|
|Remote Code Execution|Attempted RCE keywords (wget, curl, bash)|
|Brute Force IPs|IPs exceeding the request threshold|

---

## ⚡ Features

- Simple drag-and-drop GUI
    
- Live progress bar
    
- Multithreaded (no freezing)
    
- Export to **CSV** or **JSON**
    
- Auto-organized output folder
    
- Works fully offline
    

---

## 🛠️ Tech Stack

- Python 3
    
- Tkinter (for GUI)
    
- Pandas (for data handling)
    
- Regex (for attack detection)
    

---

## 📌 Future Enhancements

- Dark mode with `ttkbootstrap`
    
- More attack pattern detection (RFI, LFI, CSRF, etc.)
    
- Batch file analysis
    
- Email alert integration
    

---

## 🤝 Contributing

Feel free to fork the project, submit issues, or create pull requests!  
Ideas, improvements, and bug fixes are always welcome.

---

## 📄 License

This project is licensed under the MIT License.

---

## ✨ Author

Made with ❤️ by [RicheByte](https://github.com/RicheByte)

---

