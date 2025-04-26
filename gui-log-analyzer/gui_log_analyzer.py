import tkinter as tk
from tkinter import filedialog, messagebox, ttk
import threading
import os
import re
import json
import pandas as pd
from collections import Counter
from datetime import datetime

# Suspicious Patterns (precompiled for speed)
SUSPICIOUS_PATTERNS = {
    'sql_injection': re.compile(r"(?i)(select|union|from|drop|where|'--|;|or\s+\d+=\d+)"),
    'xss': re.compile(r"(?i)(<script>|alert\(|onerror=|onload=)"),
    'path_traversal': re.compile(r"(?i)(\.\./|\.\.%2f|%2e%2e/)"),
    'scanner_detection': re.compile(r"(?i)(sqlmap|nikto|wpscan|acunetix)"),
    'rce': re.compile(r"(?i)(wget|curl|bash|python|perl)")
}

def parse_log_line(line):
    match = re.match(r'(\S+) - - \[(.*?)\] "(\S+) (\S+) \S+" (\d+) \d+ "(.*?)" "(.*?)"', line)
    if match:
        ip, timestamp, method, path, status, referer, user_agent = match.groups()
        return {'ip': ip, 'timestamp': timestamp, 'method': method, 'path': path, 'status': int(status), 'referer': referer, 'user_agent': user_agent}
    return None

def detect_attacks(log):
    attacks = []
    for attack_type, pattern in SUSPICIOUS_PATTERNS.items():
        if pattern.search(log['path']) or pattern.search(log['referer']) or pattern.search(log['user_agent']):
            attacks.append({
                'type': attack_type,
                'ip': log['ip'],
                'timestamp': log['timestamp'],
                'path': log['path'],
                'details': f"Potential {attack_type} attempt",
                'severity': 'High' if attack_type in ['sql_injection', 'xss', 'rce'] else 'Medium'
            })
    return attacks

def analyze_file(file_path, output_format, threshold, progress_callback, done_callback):
    logs = []
    with open(file_path, 'r') as f:
        for line in f:
            log = parse_log_line(line)
            if log:
                logs.append(log)

    findings = []
    ip_counter = Counter(log['ip'] for log in logs)

    for idx, log in enumerate(logs):
        findings.extend(detect_attacks(log))
        if idx % 50 == 0:
            progress_callback(int(idx / len(logs) * 100))

    for ip, count in ip_counter.items():
        if count > threshold:
            findings.append({
                'type': 'brute_force',
                'ip': ip,
                'details': f"High request count: {count}",
                'severity': 'Medium'
            })

    timestamp = datetime.now().strftime('%Y%m%d_%H%M%S')
    output_dir = "gui_log_results"
    os.makedirs(output_dir, exist_ok=True)
    output_file = os.path.join(output_dir, f"log_result_{timestamp}.{output_format}")

    if output_format == 'csv':
        pd.DataFrame(findings).to_csv(output_file, index=False)
    else:
        with open(output_file, 'w') as f:
            json.dump(findings, f, indent=2)

    done_callback(findings, output_file)

class LogAnalyzerApp:
    def __init__(self, root):
        self.root = root
        root.title("🚀 Log Analyzer Pro")

        self.file_label = tk.Label(root, text="No file selected")
        self.file_label.pack(pady=5)

        tk.Button(root, text="Select Log File", command=self.select_file).pack(pady=5)
        tk.Button(root, text="Analyze", command=self.start_analysis).pack(pady=5)

        self.progress = ttk.Progressbar(root, length=300)
        self.progress.pack(pady=5)

        self.output_var = tk.StringVar(value="json")
        tk.Radiobutton(root, text="JSON", variable=self.output_var, value="json").pack(side="left", padx=10)
        tk.Radiobutton(root, text="CSV", variable=self.output_var, value="csv").pack(side="left")

        self.threshold_var = tk.IntVar(value=100)
        tk.Label(root, text="Brute-force Threshold:").pack(pady=5)
        tk.Entry(root, textvariable=self.threshold_var).pack(pady=5)

        self.file_path = None

    def select_file(self):
        filetypes = (("Log files", "*.log *.txt"), ("All files", "*.*"))
        self.file_path = filedialog.askopenfilename(title="Open log file", filetypes=filetypes)
        if self.file_path:
            self.file_label.config(text=os.path.basename(self.file_path))

    def start_analysis(self):
        if not self.file_path:
            messagebox.showwarning("Warning", "Please select a file first!")
            return
        self.progress['value'] = 0
        threading.Thread(target=analyze_file, args=(
            self.file_path, self.output_var.get(), self.threshold_var.get(),
            self.update_progress, self.analysis_done
        ), daemon=True).start()

    def update_progress(self, value):
        self.progress['value'] = value

    def analysis_done(self, findings, output_file):
        self.progress['value'] = 100
        messagebox.showinfo("Done", f"Found {len(findings)} suspicious activities.\nResults saved to:\n{output_file}")

if __name__ == "__main__":
    root = tk.Tk()
    app = LogAnalyzerApp(root)
    root.mainloop()

