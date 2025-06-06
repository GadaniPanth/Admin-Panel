import { Component, OnInit } from "@angular/core";
import { AdminService } from "src/app/_services/admin.service";
import { ToastrService } from "ngx-toastr";
import { ActivatedRoute, Router } from "@angular/router";
import { ExcelService } from "src/app/_services/excel.service";
import { PagerService } from "src/app/_services/pager-service";

@Component({
  selector: "app-list-user",
  templateUrl: "./list-user.component.html",
  styleUrls: ["./list-user.component.css"],
})
export class ListUserComponent implements OnInit {
  constructor(
    private adminService: AdminService,
    private toastr: ToastrService,
    private router: Router,
    private excelService: ExcelService,
    private pagerService: PagerService,
    private route: ActivatedRoute
  ) { }

  usersList: any[] = [];
  usersCount: number = 0;

  ngOnInit() {
    this.route.queryParams.subscribe(params => {
      this.userObj.user_type_id = params['user_type_id'] ? params['user_type_id'] : '';
    });
    this.loadUserList();
    this.userStatusCounter();

    this.user_status_list = [
          { type: "Active", status: 1 },
          { type: "Inactive", status: 0 },
        ];

    // p

    this.permissionObj = this.adminService.userpermissions;

    for (const key in this.permissionObj) {
      if (this.permissionObj[key]['slug'] == 'users') {
        for (const k in this.permissionObj[key]) {
          if (this.permissionObj[key][k] == 1) {
            this.permissions.push(k);
          }
        }
      }
    }


    // p
  }
  permissions: any = [];
  permissionObj: any;

  userObj: any = {
    page: 1,
    limit: 10,
    search: "",
    status: null,
    user_type_id: '',
    total: 0,
    sort_by: "",
    sort_order: "",
    from_date: "",
    to_date: "",
    order_by: "",
    order_type: "",
  };
  user_type_list: any[] = [];
  user_status_list: any[] = [];
  total_records: any = 0;

  isLoading: any = false;

  onFilter() {
    this.userObj.page = 1;
    this.loadUserList();
  }

  onSearchChange() {
    this.userObj.page = 1;
    this.loadUserList();

    // this.searchSubject.next(this.categoryObj.search); // Trigger search with debounce
  }

  loadUserList() {
    this.isLoading = true;
    this.usersList = [];
    // this.adminService.get_users_list(this.userObj).subscribe((res) => {
    this.adminService.get_users_list(this.userObj).subscribe((res) => {
      if (res.success == 1) {
        this.usersList = res.list;
        this.usersCount = res.total_records;
        this.userObj.total = res.total_records;
        
        if (this.usersList && this.usersList.length > 0) {
          this.setUsersPage(this.userObj.page, 0);
        }
        this.isLoading = false;
        
      } else {
        this.usersList = [];
        this.isLoading = false;
      }
      // const params = {
      //   search: this.userObj.search,
      //   page: this.userObj.page,
      //   limit: this.userObj.limit,
      // };
      // console.log(this.usersList);
    });

    this.adminService.get_userTypeList().subscribe((res) => {
      this.user_type_list = res.list;
    });
  }

  // isToggled: boolean = true;
  // showActions = false;
  // toggle(id, status): void {
  //   status == "1" ? "0" : "1";
  //   console.log(status, "Status");
  //   console.log(id, "Id");
  //   console.log(this.usersList);
  //   this.usersList.forEach((element) => {
  //     console.log(element);
  //     if (element.user_type_id == id) {
  //       element.is_active == status;
  //     }
  //   });
  // }

  isToggled: boolean = true;
  // showActions = false;
  toggle(id, status): void {
    // console.log(status, "Status");
    // console.log(status, "Status");
    // console.log(id, "Id");
    // console.log(this.form_List);

    status = status == "1" ? "0" : "1";
    this.adminService
      .changeStatusUser({ user_id: id, is_active: status })
      .subscribe((res) => {
        if (res.success) {
          this.toastr.success(res.message);
          // this.usersList();
          this.usersList.forEach((element: any) => {
            if (element.user_id == id) {
              // console.log(element.is_active);
              element.is_active = status;
            }
          });
        } else {
          this.toastr.success(res.message);
        }
      });
  }

  onEdit(id: number) {
    // console.log(id);
    this.router.navigate(["/edit-user"], { queryParams: { user_id: id } });
  }

  // onDelete(id: number){
  //   // console.log(id);
  //   this.adminService.delete_user(id).subscribe((res)=>{
  //     if(res.success){
  //       this.toastr.success(res.message);
  //       this.loadUserList();
  //     }else {
  //       this.toastr.error(res.message);
  //     }
  //   });
  //   // this.loadUserList();
  // }

  // delete

  public deleteObj: any = {
    user_id: "",
  };


  public isDelete: boolean = false;
  confirmDelete() {
    if (this.isDelete == false) {
      this.isDelete = true;

      this.adminService
        .delete_user(this.deleteObj)
        .subscribe((response: any) => {
          if (response.success == 1) {
            const index = this.usersList.findIndex(
              (item) => item.user_id === this.deleteObj.user_id
            );
            if (index !== -1) {
              this.usersList.splice(index, 1);
            }
            this.deleteObj = {
              user_id: "",
            };
            this.isDelete = false;
            // this.loadUserList();
            this.toastr.success(response.message);
          } else {
            this.isDelete = false;
            // this.toastr.error(response.message);
          }
        });

      this.isDelete = false;
    }
  }

  deletepopup(id: any) {
    if (this.deleteObj.user_id == id) {
      this.deleteObj.user_id = "";
    } else {
      this.deleteObj.user_id = id;
    }
  }
  cancelCahnge() {
    this.deleteObj.user_id = "";
  }

  user_type_counter: any = {};

  userStatusCounter() {
    this.adminService.userTypeCounter({}).subscribe((res: any) => {
      if (res.success) {
        this.user_type_counter = res.data;
      }
      else {
        this.user_type_counter = {}
      }
    })
    // console.log(this.user_type_counter);
  }

  onFilterTypeTab(id: any) {
    this.router.navigate([], {
      queryParams: {
        'user_type_id': id,
      },
      queryParamsHandling: 'merge', // This keeps existing query params
    });
    if(this.userObj.user_type_id != id){
      this.userObj.user_type_id = id;
      this.userObj.page = 1;
      this.loadUserList();
    }
  }

  exportList: any = [];
  exportAsXLSX(): void {
    var i = 1;
    let t = this;
    var obj = [];

    let headers = [];
    const payload = {
      limit: 1000000,
    };
    this.adminService.get_users_list(payload).subscribe((response: any) => {
      if (response.success == 1) {
        this.exportList = response.list;

        this.exportList.map(function (a: any) {
          let exportData = {};

          exportData["Sr.No"] = i++;
          exportData["Name"] = a.name;
          exportData["Email"] = a.email;
          exportData["Contact No"] = a.contact_no;
          exportData["Email"] = a.email;
          exportData["User Id"] = a.user_type_id;
          exportData["Designation Id"] = a.designation_id;
          exportData["Status"] = a.is_active ? a.is_active : "";
          exportData["Created At"] = a.created_at_formated
            ? a.created_at_formated
            : "";
          exportData["Last Updated At"] = a.last_updated_at
            ? a.last_updated_at
            : "";
          // exportData["Deleted At"] = a.deleted_at ? a.deleted_at : "";
          // exportData['Closed At'] = a.closed_at ? a.closed_at : "";

          obj.push(exportData);
        });

        // this.excelService.exportAsExcelFile(obj, headers, "Categories");
        this.excelService.exportAsExcelFile(obj, headers, "Users");
      } else {
      }
    });
  }

  public usersPager: any = [];
  setUsersPage(page: number, flag: number) {
    this.usersPager = this.pagerService.getPager(
      this.userObj.total,
      page,
      this.userObj.limit
    );
    this.userObj.page = this.usersPager.currentPage;

    this.router.navigate([], {
      queryParams: {
        page: this.userObj.page,
      },
      queryParamsHandling: "merge", // This keeps existing query params
    });

    if (flag == 1) {
      // this.getProductList();
      this.loadUserList();
    }
  }
}
