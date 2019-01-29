<?php
/**
 * Abstract model for base CRUD functions
 *
 * @category MVC Model
 * @package  maarsson/model
 * @author   Viktor Maarsson <viktor@maarsson.se>
 * @license  http://www.opensource.org/licenses/mit-license.php MIT Licence
 * @link     http://maarsson.se/model
 */

namespace Maarsson;

use Maarsson\QueryBuilder\Insert;

class QueryBuilder
{
    public function insert($model) {
        Insert::run($model);
    }
}
?>
