<?php
class User
{
    private $_db,
        $_data,
        $_getdata,
        $_sessionName,
        $_sessionTableName,
        $_sessionTable,
        $_cookieName,
        $_override,
        $_xls;
    public $isLoggedIn;

    public function __construct($user = null)
    {
        $this->_db = DB::getInstance();
        $this->_override = new OverideData();
        $this->_sessionName = config::get('session/session_name');
        $this->_sessionTable = config::get('session/session_table');
        $this->_cookieName = config::get('remember/cookie_name');

        if (!$user) {
            if (Session::exists($this->_sessionName)) {
                $user = Session::get($this->_sessionName);
                $this->_sessionTableName = Session::getTable($this->_sessionTable);
                if ($this->findUser($user, $this->_sessionTableName)) {
                    $this->isLoggedIn = true;
                } else {
                }
            }
        } else {
            $this->find($user);
        }
    }
    public function getSessionTable()
    {
        return $this->_sessionTableName;
    }
    public function validateBundle($message, $noUser)
    {
        $noWords = $this->countWords($message, $noUser);
        if ($noWords <= $this->checkBundle()[0]['sms']) {
            return true;
        }
    }
    public function countWords($message, $noUser)
    {
        return ceil((mb_strlen($message)) / 160) * $noUser;
    }

    function dateDiff($startDate, $endDate)
    {
        $date = strtotime($endDate) - strtotime($startDate);
        return number_format($date / 86400);
    }

    function dateDiffYears($startDate, $endDate)
    {
        $date = abs(strtotime($endDate) - strtotime($startDate));
        return number_format($date / (365 * 60 * 60 * 24));
    }

    public function getOS()
    {

        global $user_agent;
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $os_platform  = "Unknown OS Platform";

        $os_array     = array(
            '/windows nt 10/i'      =>  'Windows 10',
            '/windows nt 6.3/i'     =>  'Windows 8.1',
            '/windows nt 6.2/i'     =>  'Windows 8',
            '/windows nt 6.1/i'     =>  'Windows 7',
            '/windows nt 6.0/i'     =>  'Windows Vista',
            '/windows nt 5.2/i'     =>  'Windows Server 2003/XP x64',
            '/windows nt 5.1/i'     =>  'Windows XP',
            '/windows xp/i'         =>  'Windows XP',
            '/windows nt 5.0/i'     =>  'Windows 2000',
            '/windows me/i'         =>  'Windows ME',
            '/win98/i'              =>  'Windows 98',
            '/win95/i'              =>  'Windows 95',
            '/win16/i'              =>  'Windows 3.11',
            '/macintosh|mac os x/i' =>  'Mac OS X',
            '/mac_powerpc/i'        =>  'Mac OS 9',
            '/linux/i'              =>  'Linux',
            '/ubuntu/i'             =>  'Ubuntu',
            '/iphone/i'             =>  'iPhone',
            '/ipod/i'               =>  'iPod',
            '/ipad/i'               =>  'iPad',
            '/android/i'            =>  'Android',
            '/blackberry/i'         =>  'BlackBerry',
            '/webos/i'              =>  'Mobile'
        );

        foreach ($os_array as $regex => $value)
            if (preg_match($regex, $user_agent))
                $os_platform = $value;

        return $os_platform;
    }

    function visit($client_id, $seq)
    {
        if ($this->_override->getCount('visit', 'client_id', $client_id) == 3) {
            $sq = $seq;
            foreach ($this->_override->getData('schedule') as $schedule) {
                $sq++;
                $visit_name = 'Visit ' . $sq;
                $last_visit_date = $this->_override->getlastRow('visit', 'client_id', $client_id, 'id')[0]['visit_date'];
                $nxt_visit = date('Y-m-d', strtotime($last_visit_date . ' + ' . $schedule['days'] . ' days'));
                $this->createRecord('visit', array(
                    'visit_name' => $visit_name,
                    'visit_code' => $schedule['visit'],
                    'visit_date' => $nxt_visit,
                    'visit_window' => $schedule['window'],
                    'client_id' => $client_id,
                    'seq_no' => $sq,
                    'status' => 0,
                ));
            }
        }
        return true;
    }

    function visit2($client_id, $seq, $study_id)
    {
        if ($this->_override->getCount('visit', 'client_id', $client_id) == 1) {
            $sq = $seq;
            foreach ($this->_override->getData('schedule') as $schedule) {
                if ($sq < 1) {
                    $sq += 1;
                    $visit_name = 'Day ' . $sq;
                    // $nxt_visit = date('Y-m-d', strtotime($nxt_visit . ' + 1 days'));
                    $last_visit_date = $this->_override->getlastRow('visit', 'client_id', $client_id, 'id')[0]['visit_date'];
                    $nxt_visit = date('Y-m-d', strtotime($last_visit_date . ' + ' . $schedule['days'] . ' days'));
                    $this->createRecord('visit', array(
                        'study_id' => $study_id,
                        'visit_name' => $visit_name,
                        'visit_code' => $schedule['visit'],
                        'study_id' => $study_id,
                        'expected_date' => $nxt_visit,
                        'visit_window' => $schedule['window'],
                        'client_id' => $client_id,
                        'seq_no' => $sq,
                        'status' => 0,
                        'reasons' => Input::get('reasons'),

                    ));
                }
            }
        }

        return $this->_override->getData('schedule');
    }

    function isValueInMultiArrays($value, $multiArray)
    {
        foreach ($multiArray as $element) {
            if (in_array($value, $element, true)) {
                // $this->updateRecord('cardiac', array(
                //     'main_diagnosis' => $value,
                // ), $element['id']);
                return true;
            }
        }
        return false;
    }

    function getBrowser()
    {

        global $user_agent;
        $user_agent = $_SERVER['HTTP_USER_AGENT'];

        $browser        = "Unknown Browser";

        $browser_array = array(
            '/msie/i'      => 'Internet Explorer',
            '/firefox/i'   => 'Firefox',
            '/safari/i'    => 'Safari',
            '/chrome/i'    => 'Chrome',
            '/edge/i'      => 'Edge',
            '/opera/i'     => 'Opera',
            '/netscape/i'  => 'Netscape',
            '/maxthon/i'   => 'Maxthon',
            '/konqueror/i' => 'Konqueror',
            '/mobile/i'    => 'Handheld Browser'
        );

        foreach ($browser_array as $regex => $value)
            if (preg_match($regex, $user_agent))
                $browser = $value;

        return $browser;
    }

    function getIp()
    {
        if (getenv('HTTP_CLIENT_IP'))
            $ipaddress = getenv('HTTP_CLIENT_IP');
        else if (getenv('REMOTE_ADDR'))
            $ipaddress = getenv('REMOTE_ADDR');
        else
            $ipaddress = 'UNKNOWN';
        return $ipaddress;
    }
    public function renameFile($file, $name)
    {
        rename($file, $name);
        return $name;
    }
    public function download($path)
    {
        $file = $path;
        $filename = 'PRST Constitution.pdf';
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));
        header('Accept-Ranges: bytes');
        @readfile($file);
    }
    public function readPdf($path)
    {
        $file = $path;
        $filename = 'Document.pdf';
        header('Content-type: application/pdf');
        header('Content-Disposition: inline; filename="' . $filename . '"');
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($file));
        header('Accept-Ranges: bytes');
        @readfile($file);
    }
    function customStringLength($x, $length)
    {
        if (strlen($x) <= $length) {
            return $x;
        } else {
            $y = substr($x, 0, $length) . '...';
            return $y;
        }
    }
    function removeSpecialChar($string)
    {
        return preg_replace('/[^A-Za-z0-9\-]/', '', $string);
    }

    function excelRow($x, $y)
    {
        $arr = array('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z');
        if ($x > 26) {
            if ($x % 26 == 0) {
                $v = abs($x / 26 - $x / 26);
            } else {
                $v = abs(floor($x / 26) - 1);
            }
            return $arr[$v] . '' . $arr[$y];
        } else {
            return $arr[$y];
        }
    }

    // function exportFullData($data, $file, $spreadsheet, $sheet, $ext)
    // {

    //     // Set the column headers
    //     $columns = array();
    //     $columnIndex = 'A';

    //     if ($data->num_rows > 0) {
    //         // Fetch and set the column names as headers in the first row
    //         while ($fieldinfo = $data->fetch_field()) {
    //             $sheet->setCellValue($columnIndex . '1', $fieldinfo->name);
    //             $columns[$columnIndex] = $fieldinfo->name;
    //             $columnIndex++;
    //         }

    //         // Fill data from the second row onward
    //         $rowNumber = 2;
    //         while ($row = $data->fetch_assoc()) {
    //             $columnIndex = 'A';
    //             foreach ($columns as $column) {
    //                 $sheet->setCellValue($columnIndex . $rowNumber, $row[$column]);
    //                 $columnIndex++;
    //             }
    //             $rowNumber++;
    //         }
    //     }

    //     // Determine the file format (xlsx, xls, csv, etc.)
    //     $writer = null;
    //     $filename = $file . '.' . $ext;

    //     switch ($ext) {
    //         case 'xlsx':
    //             $writer = new Xlsx($spreadsheet);
    //             header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    //             break;
    //         case 'xls':
    //             $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xls($spreadsheet);
    //             header('Content-Type: application/vnd.ms-excel');
    //             break;
    //         case 'csv':
    //             $writer = new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet);
    //             header('Content-Type: text/csv');
    //             break;
    //         default:
    //             throw new Exception('Unsupported file format');
    //     }

    //     // Set the download headers
    //     header('Content-Disposition: attachment;filename="' . $filename . '"');
    //     header('Cache-Control: max-age=0');

    //     // Save the file to output
    //     $writer->save('php://output');
    //     exit();
    // }




    function exportData($data, $file)
    {
        $timestamp = time();
        $filename = $file . '_' . $timestamp . '.xlsx';

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $isPrintHeader = false;
        foreach ($data as $row) {
            if (!$isPrintHeader) {
                echo implode("\t", array_keys($row)) . "\n";
                $isPrintHeader = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
        exit();
    }

    function exportDataXls($data, $file)
    {
        $timestamp = time();
        $filename = $file . '_' . $timestamp . '.xls';

        header("Content-Type: application/vnd.ms-excel");
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $isPrintHeader = false;
        foreach ($data as $row) {
            if (!$isPrintHeader) {
                echo implode("\t", array_keys($row)) . "\n";
                $isPrintHeader = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
        exit();
    }

    function exportDataCsv($data, $file)
    {
        $timestamp = time();
        $filename = $file . '_' . $timestamp . '.csv';

        // header("Content-Type: application/vnd.ms-csv");
        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $isPrintHeader = false;
        foreach ($data as $row) {
            if (!$isPrintHeader) {
                echo implode("\t", array_keys($row)) . "\n";
                $isPrintHeader = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
        exit();
    }

    function exportDataStata($data, $file)
    {
        $timestamp = time();
        $filename = $file . '_' . $timestamp . '.dta';

        header('Content-Type: application/octet-stream');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $isPrintHeader = false;
        foreach ($data as $row) {
            if (!$isPrintHeader) {
                echo implode("\t", array_keys($row)) . "\n";
                $isPrintHeader = true;
            }
            echo implode("\t", array_values($row)) . "\n";
        }
        exit();
    }

    function exportDataCsv1($data, $file)
    {
        $timestamp = time();
        $filename = $file . '_' . $timestamp . '.csv';

        header('Content-Type: text/csv');
        header("Content-Disposition: attachment; filename=\"$filename\"");

        $isPrintHeader = false;
        foreach ($data as $row) {
            if (!$isPrintHeader) {
                echo implode("\t", array_keys($row)) . "\n";
                $isPrintHeader = true;
            }
            echo implode("\t", array_values($row)) . "\n";
            if (strpos($row, ',') !== false) {
                $row = '"' . str_replace('"', '""', $row) . '"';
            }
        }
        exit();
    }

    function exportDataCsv2($data, $file)
    {

        // Query data
        $result = $data;

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="data.csv"');

        // Open output stream
        $fp = fopen('php://output', 'w');

        // Output column headers
        fputcsv($fp, ['Column1', 'Column2', 'Column3']);

        // Output data as CSV
        while ($row = $result->fetch_assoc()) {
            // Process each row to handle commas in text
            $processed_row = [];
            foreach ($row as $value) {
                // If the value contains a comma, enclose it in quotes
                if (strpos($value, ',') !== false) {
                    $value = '"' . str_replace('"', '""', $value) . '"';
                }
                $processed_row[] = $value;
            }
            fputcsv($fp, $processed_row);
        }

        // Close the file pointer
        fclose($fp);

        // Close MySQL connection
        // $conn->close();

    }






    public function update($fields = array(), $id = null)
    {
        if (!$id && $this->isLoggedIn()) {
            $id = $this->data()->id;
        }
        if (!$this->_db->update('user', $id, $fields)) {
            throw new Exception('There is problem updating');
        }
    }
    public function updateRecord($table, $fields = array(), $id = null)
    {
        if (!$id && $this->isLoggedIn()) {
            $id = $this->data()->id;
        }
        if (!$this->_db->update($table, $id, $fields)) {
            throw new Exception('There is problem updating');
        }
    }

    public function setSiteId($table, $fields = array(), $id = null)
    {
        if (!$id && $this->isLoggedIn()) {
            $id = $this->data()->id;
        }
        if (!$this->_db->update($table, $id, $fields)) {
            throw new Exception('There is problem updating');
        }
    }

    // public function setSiteI7d($table, $site_id, $value)
    // {
    //     $query = $this->_pdo->query("UPDATE $table SET $site_id=$value WHERE 1");
    //     $num = $query->rowCount();
    //     return $num;
    // }

    public function deleteRecord($table, $field, $value)
    {
        if (!$this->_db->delete($table, array($field, '=', $value))) {
            throw new Exception('There is problem deleting');
        }
    }

    public function createRecord($table, $fields = array())
    {
        if (!$this->_db->insert($table, $fields)) {
            throw new Exception('There is a problem creating Account');
        }
        return true;
    }

    public function find($user = null)
    {
        if ($user) {
            $field = (is_numeric($user)) ? 'id' : 'email';
            $data = $this->_db->get('staff', array($field, '=', $user));

            if ($data->count()) {
                $this->_data = $data->first();
                return true;
            }
        }
    }
    public function findUser($user = null, $table)
    {
        if ($user) {
            $field = (is_numeric($user)) ? 'id' : 'username';
            $data = $this->_db->get($table, array($field, '=', $user));

            if ($data->count()) {
                $this->_data = $data->first();
                return true;
            }
        }
    }

    public function loginUser($username = null, $password = null, $table)
    {
        if (!$username && !$password && $this->exists()) {
            Session::put($this->_sessionName, $this->data()->id);
        } else {
            $user = $this->findUser($username, $table);
            if ($user) {
                if ($this->data()->password === Hash::make($password, $this->data()->salt)) {
                    Session::put($this->_sessionName, $this->data()->id);
                    Session::putSession($this->_sessionTable, $table);
                    return true;
                }
            }
        }
        return false;
    }

    public function login($username = null, $password = null, $remember = false)
    {
        if (!$username && !$password && $this->exists()) {
            Session::put($this->_sessionName, $this->data()->id);
        } else {
            $user = $this->find($username);
            if ($user) {
                if ($this->data()->password === Hash::make($password, $this->data()->salt)) {
                    Session::put($this->_sessionName, $this->data()->id);
                    if ($remember) {
                        $hash = Hash::unique();
                        $hashCheck = $this->_db->get('user_session', array('user_id', '=', $this->data()->id));
                        if (!$hashCheck->count()) {
                            $this->_db->insert('user_session', array(
                                'user_id' => $this->data()->id,
                                'hash' => $hash
                            ));
                        } else {
                            $hash = $hashCheck->first()->hash;
                        }
                        Cookie::put($this->_cookieName, $hash, config::get('remember/cookie_expiry'));
                    }
                    return true;
                }
            }
        }
        return false;
    }

    public function exists()
    {
        return (!empty($this->_data)) ? true : false;
    }
    public function logout()
    {
        $this->_db->delete('user_session', array('user_id', '=', $this->data()->id));
        Session::delete($this->_sessionName);
        Cookie::delete($this->_cookieName);
    }
    public function data()
    {
        return $this->_data;
    }
    public function isLoggedIn()
    {
        return $this->isLoggedIn;
    }
    function report($value)
    {
        $men = 0;
        $women = 0;
        $elders = 0;
        $children = 0;
        $dependant = 0;
        $citizen = 0;
        $men = $this->_override->getCount('citizen', 'gender', 'Male');
        $women = $this->_override->getCount('citizen', 'gender', 'Female');
        $elders = $this->_override->getSumD('citizen', 'no_elder');
        $children = $this->_override->getSumD('citizen', 'no_children');
        $dependant = $this->_override->getSumD('citizen', 'no_dependant');
        $citizen = $men + $women + $elders[0]['SUM(no_elder)'] + $children[0]['SUM(no_children)'] + $dependant[0]['SUM(no_dependant)'];
        if ($citizen > 0) {
            if ($value == 'men') {
                $result = ($men / $citizen) * 100;
            } elseif ($value == 'women') {
                $result = ($women / $citizen) * 100;
            } elseif ($value == 'elders') {
                $result = ($elders[0]['SUM(no_elder)'] / $citizen) * 100;
            } elseif ($value == 'children') {
                $result = ($children[0]['SUM(no_children)'] / $citizen) * 100;
            } elseif ($value == 'dependant') {
                $result = ($dependant[0]['SUM(no_dependant)'] / $citizen) * 100;
            }
            if (!is_nan($result)) {
                return $result;
            } else {
                return 0;
            }
        }
    }

    // public function isValueInMultiArray($value, $multiArray)
    // {
    //     foreach ($multiArray as $element) {
    //         if (in_array($value, $element, true)) {
    //             // Value found in the multi-dimensional array
    //             $user->updateRecord('card_test', array(
    //                 'cardiac' => $value,
    //             ), $element['id']);

    //             return true;
    //         }
    //     }
    //     // Value not found in the multi-dimensional array
    //     return false;
    // }
}
