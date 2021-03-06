# Medici Core

Medici core package

- Repository, Pipeline
- NodesTree
- [EloquentNestedSet](#EloquentNestedSet)
  - [warning](#warning)
- ApiResponser
- StatusCode, Error Handler
- [Helpers](#Helpers)

## Helpers

Add `MediciCoreHelperServiceProvider` to `config/app.php`, example:

```injectablephp
    'providers' => [
        ...
        MediciVN\Core\Providers\MediciCoreHelperServiceProvider::class,
    ],
```

Functions:

- upload_images
- resize_image
- upload_private_images
- get_url_private
- medici_logger
- generate_random_verification_code
- upload_image_v2
- upload_private_image_v2

### Upload Image
- Upload an image
```php
    $disk = Storage::disk(env('FILESYSTEM_CLOUD_PRIVATE', 's3')); // disk driver instance
    $uploader = new Uploader($source, $disk, $path); // uploader instance
    $uploader->setSizes($size); // resize image if required
    $uploader->setFileName($fileName); // want to set a specific file name
    $uploader->upload()->getResult(); // upload and get result

    // or you can chain the methods in one line
    $uploader->setSizes($size)->setFileName($fileName)->upload()->getResult();

    // global function
    upload_image_v2($source, $path, $size, $fileName);
```

## EloquentNestedSet

Automatically update the tree when creating, updating and deleting a node.

How to use:

- First, a root node must be initialized in your model's table
- Add `use EloquentNestedSet;` to your eloquent model, example:

```injectablephp
class Category extends Model
{
    use EloquentNestedSet;

    /**
     * The root node id 
     * Default: 1 
     */
    const ROOT_ID = 99999; 

    /**
     * The left position column name
     * Default: 'lft'
     *
     * Note: 'lft' can be a negative number
     */
    const LEFT = 'lft';

    /**
     * The right position column name
     * Default: 'rgt'
     *
     * Note: 'rgt' can be a negative number
     */
    const RIGHT = 'rgt';

    /**
     * The parent's id column name
     * Default: 'parent_id'
     */
    const PARENT_ID = 'parent_id';

    /**
     * The depth column name
     * The depth of a node - nth descendant, it doesn't affect left and right calculation
     * Starting from the root node will have a depth of 0
     * Default: 'depth'
     */
    const DEPTH = 'depth';

    /**
     * The queue connection is declared in your project `config/queue.php`.
     * if QUEUE_CONNECTION and QUEUE are not provided, lft and rgt calculation are synchronized.
     * 
     * Default: null
     */
    const QUEUE_CONNECTION = 'sqs';

    /**
     * Default: null
     */
    const QUEUE = 'your_queue';

```

### Functions

- `getTree`: get all nodes and return as `nested array`
- `getFlatTree`: get all nodes and return as `flatten array`, the child nodes will be sorted after the parent node
- `getAncestors`: get all `ancestor` nodes of current instance
- `getAncestorsTree`: get all `ancestor` nodes of current instance and return as `nested array`
- `getDescendants`: get all `descendant` nodes of current instance
- `getDescendantsTree`: get all `descendant` nodes of current instance and return as `nested array`
- `parent`: get the parent node to which the current instance belongs
- `children`: get the child nodes of the current instance
- `getLeafNodes`: get all leaf nodes - nodes with no children

#### Other

- `buildNestedTree`: build a nested tree base on `parent_id`

### Query scopes

[Laravel Eloquent Query Scopes](https://laravel.com/docs/9.x/eloquent#query-scopes)

The `root` node is automatically ignored by a global scope of `ignore_root`.
To get the `root` node, use `withoutGlobalScope('ignore_root')`.

- `ancestors`
- `descendants`
- `flattenTree`
- `leafNodes`

### Warning

- If you are using `SoftDelete` and intend to stop using it, you must deal with soft deleted records.
  The tree will be shuffled, and the calculation of lft and rgt may go wrong.
- `SoftDelete` is required if you use `queue`.
  Because the `queue` will not run in `deleting` and `deleted` events if a record is permanently deleted.
