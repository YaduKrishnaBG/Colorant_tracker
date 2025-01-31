# Colorant Tracker

**Colorant Tracker** is a real-time tracking system designed to monitor the live usage of colorants. It retrieves live weight data and tracks usage in real-time, ensuring accurate and efficient management of colorant resources.

## Table of Contents

- [Features](#features)
- [Hardware Components](#hardware-components)
- [Software & Drivers](#software--drivers)
- [Installation](#installation)
- [Usage](#usage)
- [License](#license)
- [Contact](#contact)

## Features

- **Real-Time Monitoring:** Continuously tracks the usage of colorants with live weight data.
- **Integrated Hardware Support:** Compatible with industry-standard scales, printers, and scanners.
- **User-Friendly Interface:** Easy to set up and manage through intuitive software.
- **Scalable:** Suitable for various sizes of operations, from small workshops to large manufacturing facilities.

## Hardware Components

The Colorant Tracker system utilizes the following hardware components:

- **Weighing Scale:** Mettler Toledo BC60 Series
- **Label Printer:** Brother QL-820NWB Thermal Printer
- **QR Code Scanner:** Codeless QR Code Scanner

### Hardware Specifications

#### Mettler Toledo BC60 Series Weighing Scale
- **Model:** BC60 Series
- **Features:** High precision, durable construction, suitable for industrial environments.

#### Brother QL-820NWB Thermal Printer
- **Model:** QL-820NWB
- **Features:** Wireless connectivity, versatile label printing, supports various label sizes.

#### Codeless QR Code Scanner
- **Model:** Codeless QR Code Scanner
- **Features:** Fast and accurate scanning, easy integration with existing systems.

## Software & Drivers

To ensure seamless operation, the following drivers and SDKs are required:

1. **Brother QL-820NWB Label Printer Driver**
   - **Download Link:** [Brother QL820NWB Driver](https://support.brother.com/g/b/downloadtop.aspx?c=us&lang=en&prod=lpql820nwbeus)

2. **Mettler Toledo BC Scale Virtual Serial Driver (32 and 64 bit)**
   - **Download Link:** [BC Virtual Serial Driver](https://www.mt.com/au/en/home/library/software-downloads/industrial-scales/BC_Virtual_Serial_Driver.html)

3. **P-touch Editor 5.x Label Editing Software**
   - **Download Link:** [P-touch Editor 5.x](https://support.brother.com/g/b/downloadend.aspx?c=us&lang=en&prod=lpql820nwbeus&os=10011&dlid=dlfp101145_000&flang=178&type3=296)

## Installation

### Prerequisites

- **Operating System:** Windows 10 or later
- **Hardware Connections:**
  - Connect the Mettler Toledo BC60 series scale to your computer via USB.
  - Set up the Brother QL-820NWB printer using the provided drivers.
  - Install and configure the Codeless QR Code Scanner as per the manufacturer’s instructions.

### Steps

1. **Install Scale Driver:**
   - Download the BC Virtual Serial Driver from the [Mettler Toledo website](https://www.mt.com/au/en/home/library/software-downloads/industrial-scales/BC_Virtual_Serial_Driver.html).
   - Run the installer and follow the on-screen instructions.

2. **Install Printer Driver:**
   - Download the Brother QL-820NWB driver from the [Brother support page](https://support.brother.com/g/b/downloadtop.aspx?c=us&lang=en&prod=lpql820nwbeus).
   - Install the driver and ensure the printer is connected and recognized by your system.

3. **Install Label Editing Software:**
   - Download P-touch Editor 5.x from the [Brother support page](https://support.brother.com/g/b/downloadend.aspx?c=us&lang=en&prod=lpql820nwbeus&os=10011&dlid=dlfp101145_000&flang=178&type3=296).
   - Install the software and configure it for label design and printing.

4. **Set Up QR Code Scanner:**
   - Follow the manufacturer’s instructions to install and configure the Codeless QR Code Scanner.

5. **Clone the Repository:**
   ```bash
   git clone https://github.com/yourusername/Colorant_tracker.git
   cd Colorant_tracker
