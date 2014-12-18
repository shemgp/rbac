<?php
namespace PhpRbac\models;

/**
 * Rbac base class contains operations that are essentially the same for
 * permissions and roles and is inherited by both.
 *
 * @author jamesvl
 * @author abiusx
 */
abstract class BaseRbac
{
    protected $dmap;
    protected $type;
    protected $cfg;

    public function __construct($cfg)
    {
        $this->cfg = $cfg;
    }

    function rootId()
    {
        return 1;
    }

    /**
     * Return type of current instance, e.g roles, permissions
     *
     * @return string
     */
    abstract protected function type();

    /**
     * Adds a new role or permission and returns the new entry's ID.
     *
     * @param string        Title of the new entry
     * @param string        Description of the new entry
     * @param integer|null  Optional ID of the parent node in the hierarchy
     * @return integer      id of the new entry
     */
    function add($Title, $Description, $ParentID = null)
    {
        if ($ParentID === null)
            $ParentID = $this->rootId();

        $res = $this->dmap->newFirstChild($ParentID, $Title, $Description);

        return (int)$res;
    }

    /**
     * Adds a path and all its components.
     * Will not replace or create siblings if a component exists.
     *
     * @param string      Full path of a Role beginning with a / (slash), e.g.
     *                      /some/role/some/where
     * @param array|null  List of descriptions; will use an empty description
     *                      if null.
     *
     * @return integer    Number of nodes created (0 if none created)
     */
    function addPath($Path, array $Descriptions = null)
    {
        if ($Path[0] !== "/")
            throw new \Exception("The path supplied is not valid.");

        $Path = substr ( $Path, 1 );
        $Parts = explode ( "/", $Path );
        $Parent = 1;
        $index = 0;
        $CurrentPath = "";
        $NodesCreated = 0;

        foreach ($Parts as $p) {
            if (isset($Descriptions[$index]))
                $Description = $Descriptions[$index];
            else
                $Description = "";

            $CurrentPath .= "/{$p}";
            $t = $this->pathId($CurrentPath);

            if (!$t) {
                $IID = $this->add($p, $Description, $Parent);
                $Parent = $IID;
                $NodesCreated++;
            }
            else {
                $Parent = $t;
            }

            $index += 1;
        }

        return (int)$NodesCreated;
    }


    /**
     * Return count of the entity
     *
     * @return integer
     */
    function count()
    {
        return (int) $this->dmap->count();
    }


    /**
     * Returns ID of entity
     *
     * @param string $entity (Path or Title)
     *
     * @return mixed ID of entity or null
     */
    public function returnId($entity = null)
    {
        if (is_numeric($entity)) {
            return $entity;
        }

        if (substr ($entity, 0, 1) == '/') {
            $entityID = $this->pathId($entity);
        }
        else {
            $entityID = $this->titleId($entity);
        }

        return $entityID;
    }

    /**
     * Returns ID of a path.
     *
     * Path may have a character limit, depending on your database.
     * (1,000 char limit on MySQL, no limit on Postgres.)
     *
     * @todo this has a limit of 1000 characters on $Path
     *
     * @param string    The path, where a single slash is root.
     *                  e.g. /role1/role2/role3
     * @return integer|null  In primary-key id of the Path, or null if not found
     */
    public function pathId($Path)
    {
        $Path = "root" . $Path;

        // strip trailing slash
        if ($Path [strlen($Path) - 1] == "/")
            $Path = substr ( $Path, 0, strlen ( $Path ) - 1 );

        return $this->dmap->idFromPath($Path);
    }

    /**
     * Returns ID belonging to a title, and the first one on that
     *
     * @param string $Title
     * @return integer Id of specified Title
     */
    public function titleId($Title)
    {
        return $this->dmap->idFromTitle($Title);
    }


    /**
     * Return the whole record of a single entry (including Rght and Lft fields)
     *
     * @param integer $ID
     */
    protected function getRecord($ID)
    {
        $qry = "SELECT *
            FROM {$this->tblName}
            WHERE id = ?";
        $params = array($ID);

        return $this->_fetchRow($qry, $params);
    }

    /**
     * Returns title of entity
     *
     * @param integer $ID
     * @return string NULL
     */
    function getTitle($ID)
    {
        return $this->dmap->getTitleFromId($ID);
    }

    /**
     * Returns path of a node
     *
     * @param integer $ID
     * @return string path
     */
    function getPath($ID)
    {
        $res = $this->dmap->getPathForId($ID);
        $out = null;

        if (is_array($res) && count($res)) {
            foreach ($res as $r) {
                if ($r ['ID'] == 1)
                    $out = '/';
                else
                    $out .= "/" . $r['title'];

                if (strlen($out) > 1)
                    return substr ($out, 1);
                else
                    return $out;
            }
        }

        return null;
    }

    /**
     * Return description of entity
     *
     * @param integer $ID
     * @return string NULL
     */
    function getDescription($ID)
    {
        return $this->dmap->getDescriptionFromId($ID);
    }


    /**
     * Edits an entity, changing title and/or description. Maintains Id.
     *
     * @param integer $ID
     * @param string $NewTitle
     * @param string $NewDescription
     *
     */
    function edit($ID, $NewTitle = null, $NewDescription = null)
    {
        $res = $this->dmap->update($ID, $NewTitle, $NewDescription);

        return $res;
    }


    /**
     * Returns children of an entity
     *
     * @param integer $ID
     * @return array
     *
     */
    function children($ID)
    {
        return $this->dmap->getChildrenOfId($ID);
    }

    /**
     * Returns descendants of a node, with their depths in integer
     *
     * @param integer $ID
     * @return array with keys as titles and Title,ID, Depth and Description
     *
     */
    function descendants($ID)
    {
        $res = $this->dmap->descendants($ID);

        $out = array();

        if (is_array($res)) {
            foreach ($res as $v) {
                $out[$v['Title']] = $v;
            }
        }

        return $out;
    }

    /**
     * Return depth of a node
     *
     * @param integer $ID
     */
    function depth($ID)
    {
        return $this->dmap->depthOfId($ID);
    }

    /**
     * Returns parent of a node
     *
     * @param integer $ID
     * @return array including Title, Description and ID
     *
     */
    function parentNode($ID)
    {
        return $this->dmap->parentNodeOfId($ID);
    }


    /**
     * Reset the table back to its initial state
     * Keep in mind that this will not touch relations
     *
     * @param boolean $Ensure
     *            must be true to work, otherwise an \Exception is thrown
     * @throws \Exception
     * @return integer number of deleted entries
     *
     */
    function reset($Ensure = false)
    {
        if ($Ensure !== true) {
            throw new \Exception("You must pass true to this function, otherwise it won't work.");
            return;
        }

        $res = $this->dmap->reset();

        return (int)$res['output'];
    }

    /**
     * Remove all role-permission relations
     * mostly used for testing
     *
     * @param boolean $Ensure
     *            must be set to true or throws an \Exception
     * @return number of deleted assignments
     */
    function resetAssignments($Ensure = false)
    {
        if ($Ensure !== true)
        {
            throw new \Exception ("You must pass true to this function, otherwise it won't work.");
            return;
        }

        $res = $this->dmap->resetAssignments();
        $this->assign ($this->rootId(), $this->rootId());

        return $res;
    }


    /**
     * @deprecated
     **/
    public function tablePrefix()
    {
        return $this->cfg['pfx'];
    }
}
