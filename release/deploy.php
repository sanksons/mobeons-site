<?php
try {
    $release = new Release();
    $release->parseRequest();
    
    $release->getTagInfo();
    
    $release->fetchTar();
    
    $release->deployTar();
    echo PHP_EOL;
    echo "SUCCESS";
} catch (Exception $e) {
    echo $e->getMessage();
    echo PHP_EOL;
    echo "ERROR";
}
echo PHP_EOL;

class Release
{

    private $tagVersion;

    private $tarballname;

    private $tarballURL;

    private $tarprefix = 'sanksons-mobeons-site';

    private $commitHashSmall;

    private $commitHash;

    private $removeFiles = array(
        'node_modules'
    );

    private $secret = 'releasemeplease';

    private $tagsUrl = 'http://api.github.com/repos/sanksons/mobeons-site/tags';

    public function parseRequest()
    {   echo 'Parse Incoming Request ...';
        $data = json_decode(file_get_contents('php://input'), true);
        
        $sec = $_GET['sec'];
        if ($sec != $this->secret) {
            throw new Exception('Invalid Auth', 500);
        }
        if (!empty($_GET['tag'])) {
            $this->tagVersion = $_GET['tag'];
        }
    }

    public function getTagInfo()
    {
        echo PHP_EOL;
        echo 'Fetching Tags Info ...' . PHP_EOL;
        echo 'URl:' . $this->tagsUrl . PHP_EOL;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tagsUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
        $output = curl_exec($ch);
        $errno = curl_errno($ch);
        curl_close($ch);
        if ($errno) {
            var_dump($errno);
            throw new Exception("cURL error ({$errno}):\n {$error_message}", 500);
        }
        $result = json_decode($output, true);
        if (empty($result)) {
            throw new Exception("Json Decode Failed", 500);
        }
        $found = false;
        foreach ($result as $tag) {
            if (empty($this->tagVersion)) {
                $this->tagVersion = $tag['name'];
            }
            if ($tag['name'] == $this->tagVersion) {
                if ($this->getCurrentRelease() == $tag['name']) {
                    //release already deployed.
                    throw new Exception('Release '.$tag['name'].'Already Deployed', 500);
                }
                $this->tarballname = $tag['name']. '.tar.gz';
                $this->tarballURL = $tag['tarball_url'];
                $this->commitHash = $tag['commit']['sha'];
                $this->commitHashSmall = substr($this->commitHash, 0, 7);
                $found = true;
                break;
            }
        }
        if (! $found) {
            throw new Exception("None of The tags matched", 500);
        }
        echo 'Tar Ball URL : ' . $this->tarballURL . PHP_EOL;
        echo 'Commit Hash  : ' . $this->commitHash . PHP_EOL;
        echo 'Small Hash   : ' . $this->commitHashSmall . PHP_EOL;
        return $this;
    }
    
    public function updateReleaseFile() {
        file_put_contents("release.txt", $this->tagVersion);
    }
    
    public function getCurrentRelease() {
        return file_get_contents("release.txt", $filename);
    }

    public function fetchTar()
    {
        echo PHP_EOL;
        echo 'Fetching Tar Ball, This will take time..... Be Patient!' . PHP_EOL;
        $fp = fopen($this->tarballname, 'w+');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->tarballURL);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch ,CURLOPT_FILE, $fp);
       
        curl_setopt($ch, CURLOPT_TIMEOUT, 5000000);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64; rv:17.0) Gecko/20100101 Firefox/17.0');
        curl_exec($ch);
        $errno = curl_errno($ch);
        //print_r(curl_getinfo($ch));
        curl_close($ch);
        fclose($fp);
        if ($errno) {
            throw new Exception("cURL error ({$errno}):\n {$error_message}", 500);
        }
        return $this;
    }

    public function deployTar()
    {
        $untarCmd = "tar -xzvf {$this->tarballname}";
        // Extract tar
        echo 'Executing: ' . $untarCmd . PHP_EOL;
        $out = $ret = NULL;
        exec($untarCmd, $out, $ret);
        if ($ret != 0) {
            throw new Exception('Could not untar', 500);
        }
        $folderName = $this->tarprefix . '-' . $this->commitHashSmall;
        
        // Remove Unnecessary files to Preserve space.
        foreach ($this->removeFiles as $file) {
            $rmCmd = "rm -rf {$folderName}/{$file}";
            echo 'Removing : ' . $rmCmd . PHP_EOL;
            $out = $ret = NULL;
            exec($rmCmd, $out, $ret);
            if ($ret != 0) {
                throw new Exception('Could not remove file' . $file, 500);
            }
        }
        // Move Files To location
        $mvCmd = "cp -r {$folderName}/* ../";
        echo 'Copying: ' . $mvCmd . PHP_EOL;
        $out = $ret = NULL;
        exec($mvCmd, $out, $ret);
        if ($ret != 0) {
            throw new Exception('Could not Move Files', 500);
        }
        $this->updateReleaseFile();
        
        //clean mess
        $rmCmd = "rm -rf {$folderName} $this->tarballname";
        exec($rmCmd, $out, $ret);
        
        return $this;
    }

}




