<?php
namespace \PhpRbac\dmap\mysql;

class UserDmap extends dmap\mysql\UserDmap {

    public function check($userId, $permId)
    {
        $LastPart = "AS Temp ON (TR.ID = Temp.RoleID)
                          WHERE TUrel.UserID = ?
                            AND Temp.ID=?";


        $Res=Jf::sql ( "SELECT COUNT(*) AS Result
            FROM
            {$this->tablePrefix()}userroles AS TUrel

            JOIN {$this->tablePrefix()}roles AS TRdirect ON (TRdirect.ID=TUrel.RoleID)
            JOIN {$this->tablePrefix()}roles AS TR ON ( TR.Lft BETWEEN TRdirect.Lft AND TRdirect.Rght)
            JOIN
            (    {$this->tablePrefix()}permissions AS TPdirect
            JOIN {$this->tablePrefix()}permissions AS TP ON ( TPdirect.Lft BETWEEN TP.Lft AND TP.Rght)
            JOIN {$this->tablePrefix()}rolepermissions AS TRel ON (TP.ID=TRel.PermissionID)
            ) $LastPart",
            $UserID, $PermissionID );

        return $Res [0] ['Result'] >= 1;
        $qry = "SELECT COUNT(*) AS Result
                  FROM {$this->pfx}userroles AS TUrel
                  JOIN {$this->pfx}roles AS TRdirect ON (TRdirect.ID=TUrel.RoleID)
                  JOIN {$this->pfx}roles AS TR ON ( TR.Lft BETWEEN TRdirect.Lft AND TRdirect.Rght)
                  JOIN 
                  (        {$this->pfx}permissions AS TPdirect
                      JOIN {$this->pfx}permissions AS TP ON ( TPdirect.Lft BETWEEN TP.Lft AND TP.Rght)
                      JOIN {$this->pfx}rolepermissions AS TRel ON (TP.ID=TRel.PermissionID)
                  ) $LastPart"

        $params = array($UserID, $PermissionID);

        $res = $this->_fetchOne($qry, $params);

        return $res !== null && $res >= 1;
    }

}
