# HOW TO DEPLOY DENTIVAULT TO VERCEL (STEP-BY-STEP GUIDE)

This guide walks you through deploying the **entire DentiVault system (Frontend + PHP Backend + SQLite Database)** to Vercel so you get a live public `.vercel.app` link for your teacher.

---

## What We Have Already Prepared for You:
1. **`vercel.json`:** Configured with the `vercel-php` runtime, environment keys (`APP_KEY`, `PEPPER_V1`), and routing rules.
2. **`api/index.php`:** Configured to automatically prepare the SQLite database in `/tmp/database.sqlite` and serve Laravel in Vercel's serverless cloud.
3. **Frontend & Assets:** Static CSS and JavaScript configured to serve directly through Vercel's global CDN.

---

## 3 Simple Steps to Deploy on Vercel:

### Step 1: Upload Your Project to GitHub
1. Go to [github.com](https://github.com) and sign in.
2. Click the green **"New"** button to create a new repository:
   * **Repository Name:** `dentivault-clinic`
   * Choose **Public** or **Private**.
   * Click **"Create repository"**.
3. In your project folder on your computer, push the code to GitHub:
   ```powershell
   cd C:\Users\NATINO\Desktop\SecureAuthProject
   git add -A
   git commit -m "feat: configure full project for vercel serverless deployment"
   git branch -M main
   git remote add origin https://github.com/YOUR_USERNAME/dentivault-clinic.git
   git push -u origin main
   ```
   *(Replace `YOUR_USERNAME` with your actual GitHub username).*

---

### Step 2: Import into Vercel
1. Go to [vercel.com](https://vercel.com) and log in (choose **"Continue with GitHub"**).
2. On your Vercel Dashboard, click **"Add New..."** ➔ **"Project"**.
3. Under *Import Git Repository*, find **`dentivault-clinic`** and click **"Import"**.

---

### Step 3: Click Deploy!
1. Under *Project Name*, you can type: `dentivault-clinic`.
2. Under *Framework Preset*, leave it as **Other**.
3. Under *Root Directory*, leave it as **`./`** (root).
4. Click the blue **"Deploy"** button!

---

## What Happens Next:
* Vercel will install dependencies, configure PHP 8.2, and deploy your project in about **1 to 2 minutes**.
* You will see fireworks on the screen with your live production URL:  
  👉 **`https://dentivault-clinic.vercel.app`** (or your custom name).

---

## How to Test Your Live Vercel Link:
1. Open your new `https://your-project.vercel.app` link in any browser or on your phone!
2. You can log in with your seeded administrator account:
   * **Email:** `admin@dentivault.com`
   * **Password:** `Admin@Dental2026!`
3. Send the link directly to your teacher!
