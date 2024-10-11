<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cat_workenvs', function (Blueprint $table) {
            $table->id('idWorkEnv')->autoIncrement()->nullable(false);
            $table->string('nameW', 45)->nullable(false);
            $table->string('type', 45)->nullable(false);
            $table->string('descriptionW', 100)->nullable();
            $table->date('date_start')->nullable(false);
            $table->date('date_end')->nullable(false);
            $table->integer('logicdeleted')->nullable();
            $table->timestamps();
            $table->string('codeWork', 100)->nullable();
        });

        Schema::create('rel_join_workenv_users', function (Blueprint $table) {
            $table->id('idJoinUserWork')->autoIncrement()->nullable(false);
            $table->integer('approbed')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->integer('privilege')->nullable();
            $table->string('token', 255)->nullable(); // Token (opcional)
            $table->timestamps();
            $table->unsignedBigInteger('idWorkEnv'); 
            $table->unsignedBigInteger('idUser'); 
            $table->foreign('idUser')->references('idUser')->on('users')->onDelete('cascade');
            $table->foreign('idWorkEnv')->references('idWorkEnv')->on('cat_workenvs')->onDelete('cascade');
        });


        Schema::create('cat_boards', function (Blueprint $table) {
            $table->id('idBoard')->autoIncrement()->nullable(false);
            $table->string('nameB', 45)->nullable(false);
            $table->string('descriptionB', 100)->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('idWorkEnv'); 
            $table->foreign('idWorkEnv')->references('idWorkEnv')->on('cat_workenvs')->onDelete('cascade');
        });

        Schema::create('cat_lists', function (Blueprint $table) {
            $table->id('idList')->autoIncrement()->nullable(false);
            $table->string('nameL', 45)->nullable(false);
            $table->string('descriptionL', 100)->nullable();
            $table->string('colorL', 100)->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('idBoard'); 
            $table->foreign('idBoard')->references('idBoard')->on('cat_boards')->onDelete('cascade');
        });


        Schema::create('cat_cards', function (Blueprint $table) {
            $table->id('idCard')->autoIncrement()->nullable(false);
            $table->string('nameC', 45)->nullable(false);
            $table->string('descriptionC', 100)->nullable();
            $table->date('end_date')->nullable();
            $table->string('evidence', 255)->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->integer('approbed')->nullable();
            $table->integer('important')->nullable();
            $table->integer('done')->nullable();
            $table->timestamps();
            $table->unsignedBigInteger('idList'); 
            $table->foreign('idList')->references('idList')->on('cat_lists')->onDelete('cascade');
        });


        Schema::create('rel_cards_users', function (Blueprint $table) {
            $table->id('idCardUser')->autoIncrement()->nullable(false); // Clave primaria
            $table->unsignedBigInteger('idCard');
            $table->unsignedBigInteger('idJoinUserWork');
            $table->foreign('idCard')->references('idCard')->on('cat_cards')->onDelete('cascade');
            $table->foreign('idJoinUserWork')->references('idJoinUserWork')->on('rel_join_workenv_users')->onDelete('cascade');
            $table->integer('logicdeleted')->nullable();
            $table->timestamps(); // Created_at y Updated_at
        });

        Schema::create('cat_comments', function (Blueprint $table) {
            $table->id('idComment')->autoIncrement()->nullable(false); // Clave primaria
            $table->unsignedBigInteger('idCard');
            $table->unsignedBigInteger('idJoinUserWork');
            $table->foreign('idCard')->references('idCard')->on('cat_cards')->onDelete('cascade');
            $table->foreign('idJoinUserWork')->references('idJoinUserWork')->on('rel_join_workenv_users')->onDelete('cascade');
            $table->string('text', 255)->nullable(false);
            $table->integer('seen')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->timestamps(); // Created_at y Updated_at
        });

        Schema::create('cat_grouptasks_coordinatorleaders', function (Blueprint $table) {
            $table->id('idgrouptaskcl')->autoIncrement()->nullable(false); // Clave primaria;
            $table->unsignedBigInteger('idJoinUserWork');
            $table->foreign('idJoinUserWork')->references('idJoinUserWork')->on('rel_join_workenv_users')->onDelete('cascade');
            $table->string('name')->nullable(false);
            $table->date('startdate')->nullabe(false);
            $table->date('enddate')->nullabe(false);
            $table->integer('logicdeleted')->nullable();
            $table->timestamps(); // Created_at y Updated_at
        });

        Schema::create('cat_labels', function (Blueprint $table) {
            $table->id('idLabel')->autoIncrement()->nullable(false);
            $table->string('nameL', 45)->nullable(false);
            $table->string('colorL', 45)->nullable(false);
            $table->integer('logicdeleted')->nullable();
            $table->unsignedBigInteger('idWorkEnv');
            $table->foreign('idWorkEnv')->references('idWorkEnv')->on('cat_workenvs')->onDelete('cascade');
            $table->timestamps(); // Created_at y Updated_at
        });

        Schema::create('cat_activity_coordinatorleaders', function (Blueprint $table) {
            $table->id('idactivitycl')->autoIncrement()->nullable(false);
            $table->string('nameT', 45)->nullable(false);
            $table->string('descriptionT', 100)->nullable();
            $table->date('end_date')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->integer('important')->nullable();
            $table->integer('done')->nullable();
            $table->unsignedBigInteger('idgrouptaskcl');
            $table->unsignedBigInteger('idLabel'); 
            $table->foreign('idgrouptaskcl')->references('idgrouptaskcl')->on('cat_grouptasks_coordinatorleaders')->onDelete('cascade');
            $table->foreign('idLabel')->references('idLabel')->on('cat_labels')->onDelete('cascade');
            $table->timestamps();
        });

        Schema::create('cat_notifications', function (Blueprint $table) {
            $table->id('idNotification')->autoIncrement()->nullable(false);
            $table->string('title', 45)->nullable(false);
            $table->string('description', 255)->nullable();
            $table->string('content', 100)->nullable();
            $table->integer('seen')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->unsignedBigInteger('idUser');
            $table->foreign('idUser')->references('idUser')->on('users')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('rel_card_labels', function (Blueprint $table) {
            $table->id('idCardLabel')->autoIncrement()->nullable(false);
            $table->unsignedBigInteger('idCard');
            $table->unsignedBigInteger('idLabel');
            $table->foreign('idCard')->references('idCard')->on('cat_cards')->onDelete('cascade');
            $table->foreign('idLabel')->references('idLabel')->on('cat_labels')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('cat_tasks', function (Blueprint $table) {
            $table->id('idTask')->autoIncrement()->nullable(false);
            $table->string('nameT', 45)->nullable(false);
            $table->string('descriptionT', 255)->nullable();
            $table->date('end_date')->nullable();
            $table->integer('done')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->unsignedBigInteger('idLabel');
            $table->unsignedBigInteger('idCard');
            $table->foreign('idCard')->references('idCard')->on('cat_cards')->onDelete('cascade');
            $table->foreign('idLabel')->references('idLabel')->on('cat_labels')->onDelete('cascade');
            $table->timestamps();

        });
        
        Schema::create('cat_task_coordinatorleaders', function (Blueprint $table) {
            $table->id('idTaskCL')->autoIncrement()->nullable(false);
            $table->string('nameT', 45)->nullable(false);
            $table->string('descriptionT', 255)->nullable();
            $table->date('end_date')->nullable();
            $table->integer('done')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->unsignedBigInteger('idLabel');
            $table->unsignedBigInteger('idactivitycl');
            $table->foreign('idactivitycl')->references('idactivitycl')->on('cat_activity_coordinatorleaders')->onDelete('cascade');
            $table->foreign('idLabel')->references('idLabel')->on('cat_labels')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('cat_calendarevents', function (Blueprint $table) {
            $table->id('idCalendarEvent')->autoIncrement()->nullable(false);
            $table->string('title', 45)->nullable(false);
            $table->string('description', 255)->nullable();
            $table->string('color', 45)->nullable();
            $table->date('start')->nullable();
            $table->date('end')->nullable();
            $table->integer('logicdeleted')->nullable();
            $table->unsignedBigInteger('idJoinUserWork');
            $table->foreign('idJoinUserWork')->references('idJoinUserWork')->on('rel_join_workenv_users')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('cat_folders', function (Blueprint $table) {
            $table->id('idFolder')->autoIncrement()->nullable(false);
            $table->string('nameF', 45)->nullable(false);
            $table->unsignedBigInteger('idJoinUserWork');
            $table->integer('logicdeleted')->nullable();
            $table->foreign('idJoinUserWork')->references('idJoinUserWork')->on('rel_join_workenv_users')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('cat_files', function (Blueprint $table) {
            $table->id('idFile')->autoIncrement()->nullable(false);
            $table->string('nameA', 45)->nullable(false);
            $table->string('path', 45)->nullable(false);
            $table->integer('filesize')->nullable(false);
            $table->string('type', 45)->nullable(false);
            $table->integer('logicdeleted')->nullable();
            $table->unsignedBigInteger('idFolder');
            $table->foreign('idFolder')->references('idFolder')->on('cat_folders')->onDelete('cascade');
            $table->timestamps();

        });

        Schema::create('rel_sharedfolder_user', function (Blueprint $table) {
            $table->id('idShareFile')->autoIncrement()->nullable(false);
            $table->unsignedBigInteger('idFolder');
            $table->unsignedBigInteger('idJoinUserWork');
            $table->integer('logicdeleted')->nullable();
            $table->foreign('idJoinUserWork')->references('idJoinUserWork')->on('rel_join_workenv_users')->onDelete('cascade');
            $table->foreign('idFolder')->references('idFolder')->on('cat_folders')->onDelete('cascade');
            $table->timestamps();

        });

        
    }

    public function down(): void
    {
        
        Schema::table('cat_activity_coordinatorleaders', function (Blueprint $table) {
            $table->dropForeign(['idgrouptaskcl']);
            $table->dropForeign(['idLabel']);
        });

        Schema::table('cat_labels', function (Blueprint $table) {
            $table->dropForeign(['idWorkEnv']);
        });

        Schema::table('cat_grouptasks_coordinatorleaders', function (Blueprint $table) {
            $table->dropForeign(['idJoinUserWork']);
        });

        Schema::table('cat_comments', function (Blueprint $table) {
            $table->dropForeign(['idCard']);
            $table->dropForeign(['idJoinUserWork']);
        });

        Schema::table('rel_cards_users', function (Blueprint $table) {
            $table->dropForeign(['idCard']);
            $table->dropForeign(['idJoinUserWork']);
        });

        Schema::table('cat_cards', function (Blueprint $table) {
            $table->dropForeign(['idList']);
        });

        Schema::table('cat_lists', function (Blueprint $table) {
            $table->dropForeign(['idBoard']);
        });

        Schema::table('cat_boards', function (Blueprint $table) {
            $table->dropForeign(['idWorkEnv']);
        });

        Schema::table('rel_join_workenv_users', function (Blueprint $table) {
            $table->dropForeign(['idUser']);
            $table->dropForeign(['idWorkEnv']);
        });

        Schema::dropIfExists('rel_sharedfolder_user');
        Schema::dropIfExists('cat_files');
        Schema::dropIfExists('cat_folders');
        Schema::dropIfExists('cat_calendarevents');
        Schema::dropIfExists('cat_task_coordinatorleaders');
        Schema::dropIfExists('cat_tasks');
        Schema::dropIfExists('rel_card_labels');
        Schema::dropIfExists('cat_notifications');
        Schema::dropIfExists('cat_activity_coordinatorleaders');
        Schema::dropIfExists('cat_labels');
        Schema::dropIfExists('cat_grouptasks_coordinatorleaders');
        Schema::dropIfExists('cat_comments');
        Schema::dropIfExists('rel_cards_users');
        Schema::dropIfExists('cat_cards');
        Schema::dropIfExists('cat_lists');
        Schema::dropIfExists('cat_boards');
        Schema::dropIfExists('rel_join_workenv_users');
        Schema::dropIfExists('cat_workenvs');
    }

};
