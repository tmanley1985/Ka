<?php
    class KaModel
    {
        private $table;
        private $fields;
        private $db_connect;


        public function __construct($class_name)
        {
			$this->table=$class_name;
			$this->dbConnect();
			$this->fields=$this->getDBFields();
        }

		static public function is_assoc($array) {
			if (is_array($array))
			{
				return (bool)count(array_filter(array_keys($array), 'is_string'));
			} else {
				return 0;
			}
		}

        public function load($data)
        {
            // Check to see if it is an array
            if ($this->is_assoc($data))
            {
                foreach ($data AS $key=>$value)
                {
                    $this->fields[$key]=$value;
                }
            } else if (is_numeric($data) && ($data > 0)) {
				$data=intval($data);
                // It is an integer so load from the db using $data an key
				$sql="SELECT * FROM $this->table WHERE id = :id";
                $query=$this->db_connect->prepare($sql);
                $query->bindParam(':id', $data);
                $query->execute();
                $result=$query->fetch(PDO::FETCH_ASSOC);

				if ($result)
				{
					foreach ($result AS $key=>$value)
					{
						if (isset($this->fields[$key]))
						{
							$this->fields[$key]=$value;
						}
					}
				}
            } else {
				return 0;
			}
        }

        protected function query($sql,$params=0)
        {
            // Add the ':' to the params key
            if (is_array($params))
            {
            	foreach($params AS $key=>$value)
				{
					$params[':'.$key]=$value;
					unset($params[$key]);
				}    
            }
			$query=$this->db_connect->prepare($sql);
			if (is_array($params)) 
			{
				$query->execute($params);
			} else {
				$query->execute();
			}

            return $query->fetchAll();
        }

        public function save()
        {
            // Check to see if the id is set
            if (is_numeric($this->fields["id"]))
            {
				$id=intval($this->fields["id"]);
                // It is set, so just update the record
                $record_id=$id;
                $tempfields=$this->fields;
                unset($tempfields['id']);



                $sql="UPDATE $this->table SET ";
                $count=count($tempfields);
                $i=0;

                foreach ($tempfields AS $key=>$value)
                {
                    if (++$i===$count)
                    {
                        $sql.="$key=:$key";
                    } else {
                        $sql.="$key=:$key,";
                    }
                }
                $sql.=" WHERE id=:id";

				$tempfields['id']=$record_id;
                foreach ($tempfields AS $key=>$value)
                {
                    $tempfields[':'.$key]=$tempfields[$key];
                    unset($tempfields[$key]);
                }
                $query=$this->db_connect->prepare($sql);
                $query->execute($tempfields);
            } else {
                // there was not id, so insert a new record
                // First get the array keys
                $tempfields=$this->fields;
                // take out the id field
                unset($tempfields["id"]);

                $fieldlist=array_keys($tempfields);

                foreach ($tempfields AS $key=>$value)
                {
                    $tempfields[':'.$key]=$tempfields[$key];
                    unset($tempfields[$key]);
                }

                // Get the pdo string
                $pdo_string='';
                foreach ($fieldlist AS $key=>$value)
                {
                    if ($key==0)
                    {
                        $pdo_string=':'.$value;
                    } else {
                        $pdo_string.=', :'.$value;
                    }
                }

                $sql="INSERT INTO $this->table (".implode(', ',$fieldlist).") VALUES (".$pdo_string.")";
                $query=$this->db_connect->prepare($sql);
                $query->execute($tempfields);
				$this->fields['id']=$this->db_connect->lastInsertId();
            }
        }

        public function delete()
        {
            if (is_numeric($this->fields['id']))
            {
				$id=intval($this->fields['id']);
                $query=$this->db_connect->prepare("DELETE FROM $this->table WHERE id=:id");
                $query->execute(array(":id"=>$this->fields['id']));

				$this->fields=$this->getDBFields();
            } else {
				return 0;
			}
        }

        public function getFields()
        {
            return $this->fields;
        }

        public function getDBFields()
        {
            $query=$this->db_connect->prepare("DESCRIBE $this->table");
            $query->execute();
            $columns=$query->fetchAll(PDO::FETCH_COLUMN);

            $fields=array();
            foreach ($columns AS $key=>$value)
            {
                $fields[$value]='';
            }
			
			return $fields;

        }

        public function dbConnect()
        {
            $this->db_connect=new PDO("mysql:host=".DATABASE_HOST.";dbname=".DATABASE_NAME,DATABASE_USERNAME,DATABASE_PASSWORD);
            $this->db_connect->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
            $this->db_connect->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
        }

        public function getTable()
        {
            return $this->table;
        }
    }
?>
