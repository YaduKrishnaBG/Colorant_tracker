Option Explicit

' ----------------------------------------------------------------------------
' (1) Connect to the database using DSN
' ----------------------------------------------------------------------------
Dim conn, rs, sql
Dim machineID, colorant, weight, dateTime

Dim connectionString
connectionString = "DSN=Name_of_your_DSN;" 
' ^ Adjust user (Uid=...) and password (Pwd=...) if your DSN does not store them.

' Create ADODB connection
Set conn = CreateObject("ADODB.Connection")

On Error Resume Next
conn.Open connectionString
If Err.Number <> 0 Then
    WScript.Echo "ERROR: Could not connect to the database. " & Err.Description
    WScript.Quit
End If
On Error GoTo 0

' ----------------------------------------------------------------------------
' (2) Fetch the last entry from the colorant_usage table
' ----------------------------------------------------------------------------
sql = "SELECT machine_id, colorant_name, weight, entry_date " & _
      "FROM colorant_usage " & _
      "ORDER BY id DESC " & _
      "LIMIT 1"

Set rs = conn.Execute(sql)
If rs.EOF Then
    WScript.Echo "No entries found in colorant_usage."
    rs.Close : conn.Close
    WScript.Quit
End If

machineID = rs("machine_id")
colorant  = rs("colorant_name")
weight    = rs("weight")
dateTime  = rs("entry_date")

rs.Close
conn.Close

' ----------------------------------------------------------------------------
' (3) Create b-PAC Document object
' ----------------------------------------------------------------------------
Dim objDoc
Set objDoc = CreateObject("bpac.Document")
If objDoc Is Nothing Then
    WScript.Echo "ERROR: Could not create b-PAC Document object."
    WScript.Quit
End If

' ----------------------------------------------------------------------------
' (4) Define label and printer constants
' ----------------------------------------------------------------------------
Dim LABEL_FILE, PRINTER_NAME
LABEL_FILE   = "C:\Users\ybabu\Desktop\Flask_app\Color_Tracker_2.0\scripts\Label_final.lbx"
PRINTER_NAME = "Brother QL-800NWB"

' ----------------------------------------------------------------------------
' (5) Open the label file
' ----------------------------------------------------------------------------
Dim isOpen
isOpen = objDoc.Open(LABEL_FILE)
If isOpen <> True Then
    WScript.Echo "ERROR: Could not open label file: " & LABEL_FILE
    WScript.Quit
End If

' ----------------------------------------------------------------------------
' (6) Assign database values to the label objects
' ----------------------------------------------------------------------------
Dim textObj1, textObj2, textObj3, textObj4

Set textObj1 = objDoc.GetObject("Text1")
If textObj1 Is Nothing Then
    WScript.Echo "ERROR: No object named 'Text1' found in label."
    objDoc.Close
    WScript.Quit
End If

Set textObj2 = objDoc.GetObject("Text2")
If textObj2 Is Nothing Then
    WScript.Echo "ERROR: No object named 'Text2' found in label."
    objDoc.Close
    WScript.Quit
End If

Set textObj3 = objDoc.GetObject("Text3")
If textObj3 Is Nothing Then
    WScript.Echo "ERROR: No object named 'Text3' found in label."
    objDoc.Close
    WScript.Quit
End If

Set textObj4 = objDoc.GetObject("Text4")
If textObj4 Is Nothing Then
    WScript.Echo "ERROR: No object named 'Text4' found in label."
    objDoc.Close
    WScript.Quit
End If

' Assign values from the database
textObj1.Text = machineID
textObj2.Text = colorant
textObj3.Text = weight & " lbs"  ' If you want to show "lbs"
textObj4.Text = dateTime

' ----------------------------------------------------------------------------
' (7) Print the label
' ----------------------------------------------------------------------------
objDoc.SetPrinter PRINTER_NAME, True
objDoc.StartPrint "", 0
objDoc.PrintOut 1, 0   ' Print 1 copy
objDoc.EndPrint
objDoc.Close

'WScript.Echo "Label printed successfully"
WScript.Quit
