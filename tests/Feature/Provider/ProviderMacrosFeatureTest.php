<?php

use Anil\FastApiCrud\Tests\TestSetup\Models\PostModel;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

describe('ProviderMacrosFeatureTest', function () {
    it('adds the likeWhere macro to Builder', function () {

        //        PostModel::factory(5)->create();
        //        $query = PostModel::query()->likeWhere(['name', 'desc'], 'Test');
        //        $sql = $query->toRawSql();
        //        expect($sql)->toBe("select * from `posts` where (`name` LIKE '%Test%' or `desc` LIKE '%Test%') and `posts`.`deleted_at` is null");

        $query = PostModel::query()
            ->likeWhere(['name', 'desc', 'tags:name,id'], 'Test');
        $sql = $query->toRawSql();
        // dd($sql);
        expect($sql)->toBe("select * from `posts` where (`name` LIKE '%Test%' or `desc` LIKE '%Test%' and exists (select * from `tags` inner join `post_tag` on `tags`.`id` = `post_tag`.`tag_id` where (`posts`.`id` = `post_tag`.`post_id` or ((`name` LIKE '%Test%' or `id` LIKE '%Test%'))) and `tags`.`deleted_at` is null)) and `posts`.`deleted_at` is null");
    });

    it('adds the paginates macro to Builder', function () {
        PostModel::factory(5)->create();

        $query = PostModel::query()->paginates();
        expect($query->perPage())->toBe(15)
            ->and($query)->toBeInstanceOf(LengthAwarePaginator::class);

        request()->merge(['rowsPerPage' => 2]);
        $query = PostModel::query()->paginates();
        expect($query)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($query->perPage())->toBe(2);
        request()->merge(['rowsPerPage' => 0]);
        $query = PostModel::query()->paginates();
        expect($query->perPage())->toBe(PostModel::query()->count());
    });

    it('adds the simplePaginates macro to Builder', function () {
        PostModel::factory(5)->create();

        $query = PostModel::query()->simplePaginates();
        expect($query->perPage())->toBe(15)
            ->and($query)->toBeInstanceOf(Paginator::class);

        request()->merge(['rowsPerPage' => 5]);
        $query = PostModel::query()->simplePaginates();

        expect($query)->toBeInstanceOf(Paginator::class)
            ->and($query->perPage())->toBe(5);

        request()->merge(['rowsPerPage' => 0]);
        $query = PostModel::query()->simplePaginates();
        expect($query->perPage())->toBe(PostModel::query()->count());
    });

    it('adds the initializer macro to Builder with filters and sorting', function () {

        PostModel::factory(5)->create();
        // Simulate a request with filters and sorting
        request()->merge([
            'filters' => json_encode(['queryFilter' => 'Test']),
            'sortBy' => 'name',
            'descending' => false,
        ]);

        // Initialize the query with the initializer macro
        $query = PostModel::query()->initializer();

        // Retrieve the built query's where clauses
        $wheres = $query->getQuery()->wheres;

        // Assert that there is exactly one where clause
        expect($wheres)->toHaveCount(1)
            ->and($wheres[0]['type'])->toBe('Nested');

        // Assert that the where clause is of type 'Nested'

        // Retrieve the nested query's where clauses
        $nestedWheres = $wheres[0]['query']->wheres;

        // Assert that there are two where clauses inside the nested query
        expect($nestedWheres)->toHaveCount(2)
            ->and($nestedWheres[0])->toMatchArray([
                'type' => 'Basic',
                'column' => 'name',
                'operator' => 'LIKE',
                'value' => '%Test%',
                'boolean' => 'or',
            ])
            ->and($nestedWheres[1])->toMatchArray([
                'type' => 'Basic',
                'column' => 'desc',
                'operator' => 'LIKE',
                'value' => '%Test%',
                'boolean' => 'or',
            ]);

        // Assert the first nested where clause

        // Assert the second nested where clause

        // Retrieve the built query's order clauses
        $orders = $query->getQuery()->orders;

        // Assert that the order clause is correctly applied
        expect($orders)->toHaveCount(1)
            ->and($orders[0]['column'])->toBe('name')
            ->and($orders[0]['direction'])->toBe('asc');
    });

    it('adds the withCountWhereHas macro to Builder', function () {
        PostModel::factory(5)->hasTags(5, ['name' => 'Test'])->create();
        $query = PostModel::query()->withCountWhereHas('tags', function ($q) {
            $q->where('name', 'like', '%test%');
        }, '>=', 2);

        // Assert that the raw SQL matches the expected format
        $sql = $query->toRawSql();
        expect($sql)->toBe("select `posts`.*, (select count(*) from `tags` inner join `post_tag` on `tags`.`id` = `post_tag`.`tag_id` where `posts`.`id` = `post_tag`.`post_id` and `name` like '%test%' and `tags`.`deleted_at` is null) as `tags_count` from `posts` where (select count(*) from `tags` inner join `post_tag` on `tags`.`id` = `post_tag`.`tag_id` where `posts`.`id` = `post_tag`.`post_id` and `name` like '%test%' and `tags`.`deleted_at` is null) >= 2 and `posts`.`deleted_at` is null");
    });
    it('adds the paginate macro to Collection', function () {
        PostModel::factory(5)->create();
        $collection = PostModel::with('user')->get();
        $perPage = 10;

        $paginated = $collection->paginate($perPage);

        expect($paginated)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($paginated->perPage())->toBe($perPage)
            ->and($paginated->items())->toHaveCount(5);
    });

    it('initializer macro does not apply sorting when orderBy is false', function () {
        // Simulate a request with sorting
        request()->merge([
            'sortBy' => 'name',
            'descending' => false,
        ]);

        $query = PostModel::query()->initializer(orderBy: false);

        // Assert that no sorting is applied
        $orders = $query->getQuery()->orders;
        expect($orders)->toBeNull();
    });

    it('initializer macro handles invalid JSON in filters gracefully', function () {
        // Simulate a request with invalid JSON in filters
        request()->merge([
            'filters' => '{invalid_json}',
        ]);

        $query = PostModel::query()->initializer();

        // Assert that no filters are applied
        expect($query->getQuery()->wheres)->toBeEmpty();
    });

    it('initializer macro applies multiple filters correctly', function () {
        // Simulate a request with multiple filters
        request()->merge([
            'filters' => json_encode(['queryFilter' => 'Sample']),
        ]);

        $query = PostModel::query()->initializer();

        // Assert that multiple where clauses are applied
        expect($query->getQuery()->wheres)->toHaveCount(1);
    });

    it('likeWhere macro handles relational attributes correctly', function () {
        $query = PostModel::query()->likeWhere(['tags:name'], 'Great');

        // Assert that whereHas is used for the relation
        $sql = $query->toRawSql();
        expect($sql)->toContain('exists')
            ->and($sql)->toContain('tags');
    });

    it('paginates macro defaults to 15 per page when rowsPerPage is zero', function () {
        $query = PostModel::query()->paginates();

        expect($query->perPage())->toBe(15);
        // Simulate a request with 'rowsPerPage' as 0
        request()->merge(['rowsPerPage' => 0]);

        $query = PostModel::query()->paginates();

        expect($query->perPage())->toBe(15);
    });

    it('simplePaginates macro defaults to 15 per page when rowsPerPage is not set', function () {
        request()->merge(['rowsPerPage' => 0]);
        $query = PostModel::query()->simplePaginates();
        expect($query->perPage())->toBe(15);
        $query = PostModel::query()->simplePaginates();
        expect($query->perPage())->toBe(15);
    });
});
