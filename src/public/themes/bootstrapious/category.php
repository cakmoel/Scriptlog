<p class="text-big">
            <?php

            $path1 = find_request(0); // 1st path parameter requested
            $path2 = find_request(1);
            $path3 = find_request(2);
            
            $dispatcher =  new Dispatcher();
            $findParam = $dispatcher -> findRequestParam();
            
            $matched = (is_array($findParam) && array_key_exists(0, $findParam)) ? $findParam[0] : ''; // 1st parameter matched
            $param2 = (is_array($findParam) && array_key_exists(1, $findParam)) ? $findParam[1] : '';
            $param3 = (is_array($findParam) && array_key_exists(2, $findParam)) ? $findParam[2] : '';
            
          
            echo '<br>';
            echo '<pre>';
            echo $dispatcher->findRules()['keys'][1];
            echo '<br>';
            echo $dispatcher->findRules()['values'][1];
            echo '<br><br>';
            print_r($dispatcher->findRules()['keys']);
            echo '<br><br>';
            print_r($dispatcher->findRules()['values']);
            echo '</pre>';
            echo '<br>';
            echo '<pre>';
            echo "first call request with <b>findRequestParam</b> function is match: ".$matched;
            echo '<br>';
            echo "second call request with <b>findRequestPath</b> function: " .$path1 . DS . $path2 . DS . $path3;
            echo '<br>';
            echo "<b>findRequestParam</b> 2nd parameter matched by rules: ".$param2." and equal to <b>findRequestPath</b> 2nd path parameter requested: ".$path2;
            echo '<br>';
            echo "<b>Server Request URI:</b>".$_SERVER['REQUEST_URI'];
            echo "<br>";
            echo "<b>Server Request Path Info:</b>" . var_dump($_SERVER);
            echo '</pre>';
            ?>

            </p>